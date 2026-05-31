<?php

namespace App\Http\Controllers;

use App\Http\Requests\RaceSettlementRequest;
use App\Models\Race;
use App\Services\BetSettlementService;
use App\Services\RaceSettlementWriter;

class RaceSettlementController extends Controller
{
    public function __construct(
        private readonly BetSettlementService $settlementService,
        private readonly RaceSettlementWriter $settlementWriter
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
        $this->settlementWriter->replaceAll($race, $validated);
        $this->settlementService->recalculateForRace($race->id);

        return redirect()->route('races.settlement.edit', $race)->with('success', '保存しました');
    }
}
