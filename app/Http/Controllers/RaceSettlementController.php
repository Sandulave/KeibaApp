<?php

namespace App\Http\Controllers;

use App\Http\Requests\RaceSettlementRequest;
use App\Models\Race;
use App\Services\BetSettlementService;
use App\Services\RaceSettlementWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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

    public function importJson(Request $request, Race $race)
    {
        $validatedRequest = $request->validate([
            'settlement_json' => ['required', 'string'],
        ], [
            'settlement_json.required' => '取り込みJSONを入力してください。',
            'settlement_json.string' => '取り込みJSONの形式が不正です。',
        ]);

        $payload = json_decode($validatedRequest['settlement_json'], true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'settlement_json' => 'JSONとして読み込めませんでした。カンマや括弧を確認してください。',
            ]);
        }

        $validator = Validator::make(
            $payload,
            RaceSettlementRequest::rulesForRace($race),
            RaceSettlementRequest::messagesForSettlement()
        );
        RaceSettlementRequest::addAfterValidation($validator, $payload);
        $validated = $validator->validate();

        $this->settlementWriter->replaceAll($race, $validated);
        $this->settlementService->recalculateForRace($race->id);

        return redirect()
            ->route('races.settlement.edit', $race)
            ->with('success', 'JSONから結果・払戻を登録しました');
    }
}
