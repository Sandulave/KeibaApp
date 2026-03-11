<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\Bet;
use App\Enums\BetType;
use App\Models\RaceUserAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Finance\BetMoneyService;

class RaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $races = Race::orderBy('race_date', 'asc')->get();

        return view('races.index', compact('races'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('races.create', [
            'horseNameByNo' => [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'horse_count' => ['required', 'integer', 'min:1', 'max:18'],
            'normal_allowance' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'challenge_allowance' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'race_date' => ['required', 'date'],
            'course' => ['required', 'string', 'max:255'],
            'result' => ['nullable', 'string', 'max:255'],
            'is_betting_closed' => ['nullable', 'boolean'],
            'horse_names' => ['nullable', 'array'],
            'horse_names.*' => ['nullable', 'string', 'max:255'],
        ], [
            'horse_count.required' => '頭数を入力してください。',
            'horse_count.integer' => '頭数は数値で入力してください。',
            'horse_count.min' => '頭数は1以上で入力してください。',
            'horse_count.max' => '頭数は18以下で入力してください。',
            'normal_allowance.integer' => '通常配布金額は数値で入力してください。',
            'normal_allowance.min' => '通常配布金額は0以上で入力してください。',
            'normal_allowance.max' => '通常配布金額が大きすぎます。',
            'challenge_allowance.integer' => '勝負配布金額は数値で入力してください。',
            'challenge_allowance.min' => '勝負配布金額は0以上で入力してください。',
            'challenge_allowance.max' => '勝負配布金額が大きすぎます。',
        ]);

        $validated['is_betting_closed'] = $request->boolean('is_betting_closed');
        $horseNames = (array) ($validated['horse_names'] ?? []);
        unset($validated['horse_names']);

        DB::transaction(function () use ($validated, $horseNames) {
            $race = Race::create($validated);
            $this->syncRaceHorses($race, $horseNames);
        });

        return redirect()->route('races.index')->with('success', '登録しました');
    }

    /**
     * Display the specified resource.
     */
    public function show(Race $race)
    {
        $race->load('payouts');

        $payoutsSorted = $race->payouts
            ->sortBy(function ($p) {
                $enum = BetType::tryFrom($p->bet_type);

                return [
                    $enum?->order() ?? 99,
                    (string) $p->selection_key,
                ];
            })
            ->values();

        $bets = Bet::where('race_id', $race->id)
            ->with('user:id,name')
            ->orderByDesc('bought_at')
            ->get();

        $totalStake = (int)$bets->sum(fn($b) => (int)$b->stake_amount);
        $totalReturn = (int)$bets->sum(fn($b) => (int)$b->return_amount);
        $overallRoi = $totalStake > 0
            ? round(($totalReturn / $totalStake) * 100, 2)
            : null;

        return view('payouts.show', [
            'race' => $race,
            'payoutsSorted' => $payoutsSorted,
            'bets' => $bets,
            'totalStake' => $totalStake,
            'totalReturn' => $totalReturn,
            'overallRoi' => $overallRoi,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Race $race)
    {
        $horseNameByNo = $race->horses()
            ->pluck('horse_name', 'horse_no')
            ->mapWithKeys(fn ($name, $no) => [(int) $no => (string) $name])
            ->all();

        return view('races.edit', compact('race', 'horseNameByNo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Race $race)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'horse_count' => ['required', 'integer', 'min:1', 'max:18'],
            'normal_allowance' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'challenge_allowance' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'race_date' => ['required', 'date'],
            'course' => ['required', 'string', 'max:255'],
            'result' => ['nullable', 'string', 'max:255'],
            'is_betting_closed' => ['nullable', 'boolean'],
            'horse_names' => ['nullable', 'array'],
            'horse_names.*' => ['nullable', 'string', 'max:255'],
        ], [
            'horse_count.required' => '頭数を入力してください。',
            'horse_count.integer' => '頭数は数値で入力してください。',
            'horse_count.min' => '頭数は1以上で入力してください。',
            'horse_count.max' => '頭数は18以下で入力してください。',
            'normal_allowance.integer' => '通常配布金額は数値で入力してください。',
            'normal_allowance.min' => '通常配布金額は0以上で入力してください。',
            'normal_allowance.max' => '通常配布金額が大きすぎます。',
            'challenge_allowance.integer' => '勝負配布金額は数値で入力してください。',
            'challenge_allowance.min' => '勝負配布金額は0以上で入力してください。',
            'challenge_allowance.max' => '勝負配布金額が大きすぎます。',
        ]);

        $validated['is_betting_closed'] = $request->boolean('is_betting_closed');
        $horseNames = (array) ($validated['horse_names'] ?? []);
        unset($validated['horse_names']);

        DB::transaction(function () use ($race, $validated, $horseNames) {
            $race->update($validated);
            $this->syncRaceHorses($race, $horseNames);
        });

        return redirect()->route('races.index')->with('success', '更新しました');
    }

    public function reapplyAllowances(Request $request, Race $race, BetMoneyService $betMoneyService)
    {
        $validated = $request->validate([
            'normal_allowance' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'challenge_allowance' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ], [
            'normal_allowance.integer' => '通常配布金額は数値で入力してください。',
            'normal_allowance.min' => '通常配布金額は0以上で入力してください。',
            'normal_allowance.max' => '通常配布金額が大きすぎます。',
            'challenge_allowance.integer' => '勝負配布金額は数値で入力してください。',
            'challenge_allowance.min' => '勝負配布金額は0以上で入力してください。',
            'challenge_allowance.max' => '勝負配布金額が大きすぎます。',
        ]);

        if (array_key_exists('normal_allowance', $validated) || array_key_exists('challenge_allowance', $validated)) {
            $race->update([
                'normal_allowance' => (int) ($validated['normal_allowance'] ?? $race->normal_allowance),
                'challenge_allowance' => (int) ($validated['challenge_allowance'] ?? $race->challenge_allowance),
            ]);
            $race->refresh();
        }

        $result = DB::transaction(function () use ($race, $betMoneyService) {
            $adjustments = RaceUserAdjustment::query()
                ->where('race_id', $race->id)
                ->whereNotNull('challenge_choice')
                ->lockForUpdate()
                ->get();

            $selectedCount = $adjustments->count();
            $affectedCount = 0;
            $totalDiff = 0;

            foreach ($adjustments as $adjustment) {
                $newAllowance = $betMoneyService->allowanceForRaceChoice($race, $adjustment->challenge_choice);
                $oldAllowance = $adjustment->granted_allowance !== null
                    ? (int) $adjustment->granted_allowance
                    : $betMoneyService->allowanceForChoice($adjustment->challenge_choice);
                $diff = $newAllowance - $oldAllowance;

                if ($diff === 0) {
                    continue;
                }

                $user = $adjustment->user()
                    ->lockForUpdate()
                    ->first();
                if ($user === null) {
                    continue;
                }

                $adjustment->granted_allowance = $newAllowance;
                $adjustment->save();

                $user->current_balance = (int) ($user->current_balance ?? 0) + $diff;
                $user->save();

                $affectedCount++;
                $totalDiff += $diff;
            }

            return [
                'selected_count' => $selectedCount,
                'affected_count' => $affectedCount,
                'total_diff' => $totalDiff,
            ];
        });

        return back()->with(
            'success',
            "配布金額を再適用しました（選択済み {$result['selected_count']} 人 / 変更 {$result['affected_count']} 人 / 残高差分合計 " . number_format($result['total_diff']) . '円）。'
        );
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Race $race)
    {
        $race->delete();

        return redirect()->route('races.index')->with('success', '削除しました');
    }

    private function syncRaceHorses(Race $race, array $horseNames): void
    {
        $horseCount = (int) ($race->horse_count ?? 0);
        $rows = collect($horseNames)
            ->mapWithKeys(function ($name, $no) {
                $horseNo = (int) $no;
                $horseName = trim((string) $name);

                return [$horseNo => $horseName];
            })
            ->filter(fn (string $name, int $no) => $no >= 1 && $no <= 18 && $name !== '')
            ->filter(fn (string $name, int $no) => $no <= $horseCount)
            ->map(fn (string $name, int $no) => [
                'race_id' => $race->id,
                'horse_no' => $no,
                'horse_name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if (empty($rows)) {
            $race->horses()->delete();

            return;
        }

        $targetNos = collect($rows)->pluck('horse_no')->all();

        $race->horses()
            ->where(function ($q) use ($horseCount, $targetNos) {
                $q->where('horse_no', '>', $horseCount)
                    ->orWhereNotIn('horse_no', $targetNos);
            })
            ->delete();

        $race->horses()->upsert(
            $rows,
            ['race_id', 'horse_no'],
            ['horse_name', 'updated_at']
        );
    }
}
