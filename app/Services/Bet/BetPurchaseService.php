<?php

namespace App\Services\Bet;

use App\Models\Bet;
use App\Models\BetItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class BetPurchaseService
{
    /**
     * @throws ValidationException
     * @throws Throwable
     */
    public function commit(
        int $userId,
        int $raceId,
        array $cartItems,
        array $buildSnapshot,
        string $idempotencyKey
    ): bool {
        try {
            DB::transaction(function () use ($userId, $raceId, $cartItems, $buildSnapshot, $idempotencyKey): void {
                $stakeAmount = (int) collect($cartItems)->sum(fn ($item) => (int) ($item['amount'] ?? 0));

                $user = User::query()
                    ->whereKey($userId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) ($user->current_balance ?? 0) < $stakeAmount) {
                    throw ValidationException::withMessages([
                        'balance' => '現在残高を超えるため購入できません。',
                    ]);
                }

                $bet = Bet::create([
                    'user_id' => $userId,
                    'race_id' => $raceId,
                    'idempotency_key' => $idempotencyKey,
                    'stake_amount' => $stakeAmount,
                    'return_amount' => 0,
                    'hit_count' => 0,
                    'roi_percent' => 0,
                    'build_snapshot' => $buildSnapshot,
                ]);

                $now = now();
                $rows = collect($cartItems)->map(fn ($item) => [
                    'bet_id' => $bet->id,
                    'bet_type' => (string) ($item['bet_type'] ?? ''),
                    'selection_key' => (string) ($item['selection_key'] ?? ''),
                    'amount' => (int) ($item['amount'] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                BetItem::insert($rows);

                $user->current_balance = (int) ($user->current_balance ?? 0) - $stakeAmount;
                $user->save();
            }, 3);

            return false;
        } catch (Throwable $e) {
            if ($this->isDuplicateIdempotency($e) && Bet::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('user_id', $userId)
                ->where('race_id', $raceId)
                ->exists()) {
                return true;
            }

            throw $e;
        }
    }

    private function isDuplicateIdempotency(Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'uq_bets_idempotency_key')
            || str_contains($message, 'bets_idempotency_key_unique')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed: bets.idempotency_key');
    }
}

