<?php

namespace App\Services;

use App\Enums\BetType;
use App\Models\Race;
use App\Models\RacePayout;
use App\Models\RaceResultEntry;
use App\Models\RaceWithdrawal;
use Illuminate\Support\Facades\DB;

class RaceSettlementWriter
{
    public function replaceAll(Race $race, array $validated): void
    {
        DB::transaction(function () use ($race, $validated) {
            RaceResultEntry::where('race_id', $race->id)->delete();
            $resultRows = [];
            foreach ([1, 2, 3] as $rank) {
                foreach ($validated['ranks'][$rank] ?? [] as $horseNo) {
                    $resultRows[] = [
                        'race_id' => $race->id,
                        'rank' => $rank,
                        'horse_no' => (string) $horseNo,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if ($resultRows !== []) {
                RaceResultEntry::insert($resultRows);
            }

            RaceWithdrawal::where('race_id', $race->id)->delete();
            $withdrawalRows = [];
            foreach ($validated['withdrawals'] ?? [] as $horseNo) {
                $withdrawalRows[] = [
                    'race_id' => $race->id,
                    'horse_no' => (string) $horseNo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($withdrawalRows !== []) {
                RaceWithdrawal::insert($withdrawalRows);
            }

            RacePayout::where('race_id', $race->id)->delete();
            $payoutRows = [];
            foreach (($validated['payouts'] ?? []) as $betType => $rows) {
                $betTypeEnum = BetType::tryFrom((string) $betType);
                if (!$betTypeEnum || !is_array($rows)) {
                    continue;
                }

                foreach ($rows as $row) {
                    $selectionKey = trim((string) ($row['selection_key'] ?? ''));
                    $payoutRaw = $row['payout_per_100'] ?? null;
                    $payoutStr = trim((string) $payoutRaw);

                    if ($selectionKey === '' && $payoutStr === '') {
                        continue;
                    }

                    $payoutRows[] = [
                        'race_id' => $race->id,
                        'bet_type' => $betTypeEnum->value,
                        'selection_scope' => $betTypeEnum->scope(),
                        'selection_key' => $selectionKey,
                        'payout_per_100' => (int) $payoutStr,
                        'popularity' => $row['popularity'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if ($payoutRows !== []) {
                RacePayout::insert($payoutRows);
            }
        });
    }
}
