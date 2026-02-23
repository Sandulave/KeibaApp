<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\RacePayout;
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
        });
    }

    private function key(string $betType, string $selectionKey): string
    {
        return $betType.'|'.$selectionKey;
    }
}
