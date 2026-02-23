<?php

namespace App\Services;

use App\Models\Race;
use App\Models\RacePayout;
use App\Enums\BetType;
use Illuminate\Support\Facades\DB;

class RacePayoutService
{
    /**
     * payouts配列から空行除去・selection_key正規化・全削除・insertをトランザクションで行う
     * @param Race $race
     * @param array $payoutsInput
     * @return void
     */
    public function replaceAll(Race $race, array $payoutsInput): void
    {
        $now = now();
        $rows = [];

        foreach ($payoutsInput as $betType => $items) {
            if (!is_array($items)) continue;
            $betTypeEnum = BetType::tryFrom($betType);
            if (!$betTypeEnum) continue;
            $selectionScope = $betTypeEnum->scope();

            foreach ($items as $item) {
                $selectionKey = trim((string)($item['selection_key'] ?? ''));
                $payoutRaw    = $item['payout_per_100'] ?? null;

                // 空行は捨てる
                if ($selectionKey === '' && ($payoutRaw === null || $payoutRaw === '')) {
                    continue;
                }

                // payoutを数字化
                $payoutStr = preg_replace('/[^0-9]/', '', (string)$payoutRaw);
                if ($selectionKey === '' || $payoutStr === '') {
                    continue;
                }

                $normalizedKey = $this->normalizeSelectionKey($betTypeEnum, $selectionKey);

                $rows[] = [
                    'race_id' => $race->id,
                    'bet_type' => $betTypeEnum->value,
                    'selection_key' => $normalizedKey,
                    'selection_scope' => $selectionScope,
                    'payout_per_100' => (int)$payoutStr,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($race, $rows) {
            RacePayout::where('race_id', $race->id)->delete();
            if (!empty($rows)) {
                RacePayout::insert($rows);
            }
        });
    }

    /**
     * selection_keyをBetTypeルールに沿って整形
     */
    private function normalizeSelectionKey(BetType $betType, string $key): string
    {
        $key = trim($key);
        $sep = $betType->separator();
        $parts = preg_split('/[-,>]/', $key) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));
        $parts = array_map(function ($v) {
            $v = preg_replace('/[^0-9]/', '', (string)$v);
            return $v === '' ? '' : str_pad($v, 2, '0', STR_PAD_LEFT);
        }, $parts);
        $parts = array_values(array_filter($parts, fn($v) => $v !== ''));
        return implode($sep, $parts);
    }
}
