<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\RacePayout;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BetSettlementService
{
    public function recalculateForRace(int $raceId): void
    {
        $payoutMap = RacePayout::where('race_id', $raceId)
            ->get(['bet_type', 'selection_key', 'payout_per_100'])
            ->mapWithKeys(fn($p) => [
                $this->key($p->bet_type, $p->selection_key) => (int)$p->payout_per_100,
            ]);

        $hasPayouts = $payoutMap->isNotEmpty();

        DB::transaction(function () use ($raceId, $payoutMap, $hasPayouts) {
            $bets = Bet::where('race_id', $raceId)
                ->with('items')
                ->get();
            $affectedUserIds = $bets
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values();

            foreach ($bets as $bet) {
                $stakeAmount = 0;
                $returnAmount = 0;
                $hitCount = 0;

                foreach ($bet->items as $item) {
                    $amount = (int)$item->amount;
                    $stakeAmount += $amount;

                    $payoutPer100 = $payoutMap->get($this->key($item->bet_type, $item->selection_key));
                    $itemReturn = $payoutPer100 !== null
                        ? (int) intdiv($amount * (int)$payoutPer100, 100)
                        : 0;
                    $isHit = $itemReturn > 0;

                    $returnAmount += $itemReturn;
                    if ($isHit) {
                        $hitCount++;
                    }

                    if ((int)$item->return_amount !== $itemReturn || (bool)$item->is_hit !== $isHit) {
                        $item->return_amount = $itemReturn;
                        $item->is_hit = $isHit;
                        $item->save();
                    }
                }

                $bet->stake_amount = $stakeAmount;
                $bet->return_amount = $returnAmount;
                $bet->hit_count = $hitCount;
                $bet->roi_percent = $stakeAmount > 0
                    ? round(($returnAmount / $stakeAmount) * 100, 2)
                    : null;
                $bet->settled_at = $hasPayouts ? now() : null;
                $bet->save();
            }

            if ($affectedUserIds->isEmpty()) {
                return;
            }

            $userIds = $affectedUserIds->all();
            $betTotalsByUser = DB::table('bets')
                ->selectRaw('user_id, COALESCE(SUM(stake_amount), 0) as total_stake, COALESCE(SUM(return_amount), 0) as total_return')
                ->whereIn('user_id', $userIds)
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $adjustmentTotalsByUser = DB::table('race_user_adjustments')
                ->selectRaw("user_id, COALESCE(SUM(bonus_points), 0) as total_bonus_points, COALESCE(SUM(CASE challenge_choice WHEN 'challenge' THEN 30000 WHEN 'normal' THEN 10000 ELSE 0 END), 0) as total_allowance")
                ->whereIn('user_id', $userIds)
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            foreach ($userIds as $userId) {
                $betTotal = $betTotalsByUser->get($userId);
                $adjustmentTotal = $adjustmentTotalsByUser->get($userId);
                $expectedBalance = (int) ($betTotal->total_return ?? 0)
                    - (int) ($betTotal->total_stake ?? 0)
                    + (int) ($adjustmentTotal->total_bonus_points ?? 0)
                    + (int) ($adjustmentTotal->total_allowance ?? 0);

                $user = User::query()
                    ->whereKey($userId)
                    ->lockForUpdate()
                    ->first();
                if ($user === null) {
                    continue;
                }

                $user->current_balance = $expectedBalance;
                $user->save();
            }
        });
    }

    private function key(string $betType, string $selectionKey): string
    {
        return $betType.'|'.$selectionKey;
    }
}
