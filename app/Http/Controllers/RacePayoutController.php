<?php

namespace App\Http\Controllers;

use App\Http\Requests\RacePayoutStoreRequest;
use App\Models\Race;
use App\Models\RacePayout;
use App\Services\RacePayoutService;

class RacePayoutController extends Controller
{
    public function __construct(
        private readonly RacePayoutService $service
    ) {}

    public function index(Race $race)
    {
        // 管理入力導線は settlement 画面に一本化
        return redirect()->route('races.settlement.edit', $race);
    }

    public function store(RacePayoutStoreRequest $request, Race $race)
    {
        // FormRequest 側で validate 済みの前提
        $this->service->replaceAll($race, $request->input('payouts', []));

        return back()->with('success', '配当を保存しました');
    }

    public function destroy(Race $race, RacePayout $payout)
    {
        abort_unless($payout->race_id === $race->id, 404);

        $payout->delete();

        return back()->with('success', '配当を削除しました');
    }
}
