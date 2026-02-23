<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\Bet;
use App\Enums\BetType;
use Illuminate\Http\Request;

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
        return view('races.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'horse_count' => ['required', 'integer', 'min:1', 'max:18'],
            'race_date' => ['required', 'date'],
            'course' => ['required', 'string', 'max:255'],
            'result' => ['nullable', 'string', 'max:255'],
        ], [
            'horse_count.required' => '頭数を入力してください。',
            'horse_count.integer' => '頭数は数値で入力してください。',
            'horse_count.min' => '頭数は1以上で入力してください。',
            'horse_count.max' => '頭数は18以下で入力してください。',
        ]);

        Race::create($validated);

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
        return view('races.edit', compact('race'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Race $race)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'horse_count' => ['required', 'integer', 'min:1', 'max:18'],
            'race_date' => ['required', 'date'],
            'course' => ['required', 'string', 'max:255'],
            'result' => ['nullable', 'string', 'max:255'],
        ], [
            'horse_count.required' => '頭数を入力してください。',
            'horse_count.integer' => '頭数は数値で入力してください。',
            'horse_count.min' => '頭数は1以上で入力してください。',
            'horse_count.max' => '頭数は18以下で入力してください。',
        ]);

        $race->update($validated);

        return redirect()->route('races.index')->with('success', '更新しました');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Race $race)
    {
        $race->delete();

        return redirect()->route('races.index')->with('success', '削除しました');
    }
}
