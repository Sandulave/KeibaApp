<?php

namespace App\Http\Controllers;

use App\Enums\BetType;
use App\Http\Requests\RaceSettlementRequest;
use App\Models\Race;
use App\Models\RaceResultEntry;
use App\Models\RacePayout;
use App\Models\RaceWithdrawal;
use App\Services\BetSettlementService;
use Illuminate\Support\Facades\DB;

class RaceSettlementController extends Controller
{
    public function __construct(
        private readonly BetSettlementService $settlementService
    ) {}

    public function edit(Race $race)
    {
        $race->load([
            'resultEntries',
            'withdrawals',
            'payouts',
        ]);

        $resultByRank = collect([1,2,3])->mapWithKeys(fn($rank) =>
            [$rank => $race->resultEntries->where('rank', $rank)->sortBy('horse_no')->pluck('horse_no')->all()]
        );
        $withdrawals = $race->withdrawals->pluck('horse_no')->all();
        $payouts = $race->payouts
            ->groupBy('bet_type')
            ->map(fn($rows) => $rows
                ->sortBy('selection_key')
                ->values()
                ->map(fn($p) => $p->only(['selection_key', 'payout_per_100', 'popularity']))
                ->all())
            ->all();

        return view('races.settlement_edit', compact('race', 'resultByRank', 'withdrawals', 'payouts'));
    }

    public function update(RaceSettlementRequest $request, Race $race)
    {
        $validated = $request->validated();
        DB::transaction(function() use ($race, $validated) {
            // 結果
            RaceResultEntry::where('race_id', $race->id)->delete();
            $rows = [];
            foreach ([1,2,3] as $rank) {
                foreach ($validated['ranks'][$rank] ?? [] as $horse_no) {
                    $rows[] = [
                        'race_id' => $race->id,
                        'rank' => $rank,
                        'horse_no' => (string)$horse_no,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if ($rows) RaceResultEntry::insert($rows);

            // 取消
            RaceWithdrawal::where('race_id', $race->id)->delete();
            $wrows = [];
            foreach ($validated['withdrawals'] ?? [] as $horse_no) {
                $wrows[] = [
                    'race_id' => $race->id,
                    'horse_no' => (string)$horse_no,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if ($wrows) RaceWithdrawal::insert($wrows);

            // 配当（券種ごと多次元配列対応）
            RacePayout::where('race_id', $race->id)->delete();
            $prows = [];
            foreach (($validated['payouts'] ?? []) as $betType => $rows) {
                $betTypeEnum = BetType::tryFrom((string)$betType);
                if (!$betTypeEnum || !is_array($rows)) {
                    continue;
                }

                foreach ($rows as $row) {
                    $selectionKey = trim((string)($row['selection_key'] ?? ''));
                    $payoutRaw = $row['payout_per_100'] ?? null;
                    $payoutStr = trim((string)$payoutRaw);

                    // 完全空行は保存しない
                    if ($selectionKey === '' && $payoutStr === '') {
                        continue;
                    }

                    $prows[] = [
                        'race_id' => $race->id,
                        'bet_type' => $betTypeEnum->value,
                        'selection_scope' => $betTypeEnum->scope(),
                        'selection_key' => $selectionKey,
                        'payout_per_100' => (int)$payoutStr,
                        'popularity' => $row['popularity'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if ($prows) RacePayout::insert($prows);
        });

        $this->settlementService->recalculateForRace($race->id);

        return redirect()->route('races.settlement.edit', $race)->with('success', '保存しました');
    }
}
