<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use App\Services\BetSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProxyEntryController extends Controller
{
    private const PROXY_USER_SESSION_KEY = 'admin_proxy.user_id';

    public function edit(Request $request): View
    {
        $adminRoles = config('domain.roles.admin', ['admin', 'kannrisyato']);
        $selectedUserId = (int) $request->query('user_id', 0);

        $users = User::query()
            ->select(['id', 'name', 'display_name', 'role'])
            ->whereNotIn('role', $adminRoles)
            ->orderBy('id')
            ->get();

        $races = Race::query()
            ->select(['id', 'name', 'race_date', 'is_betting_closed'])
            ->orderByDesc('race_date')
            ->orderByDesc('id')
            ->get();

        $selectedUser = $selectedUserId > 0 ? $users->firstWhere('id', $selectedUserId) : null;
        $proxyUserId = (int) $request->session()->get(self::PROXY_USER_SESSION_KEY, 0);
        $proxyUser = $proxyUserId > 0 ? $users->firstWhere('id', $proxyUserId) : null;

        $bets = collect();
        if ($selectedUser !== null) {
            $bets = Bet::query()
                ->where('user_id', $selectedUser->id)
                ->with([
                    'race:id,name,race_date',
                    'items' => fn ($q) => $q->orderBy('id'),
                ])
                ->orderByDesc('race_id')
                ->orderByDesc('bought_at')
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        return view('admin.proxy_entry', [
            'users' => $users,
            'races' => $races,
            'selectedUserId' => $selectedUserId,
            'selectedRaceId' => 0,
            'selectedUser' => $selectedUser,
            'selectedRace' => null,
            'adjustment' => null,
            'bets' => $bets,
            'betTypeLabels' => config('domain.bet.type_labels', []),
            'proxyUser' => $proxyUser,
        ]);
    }

    public function startBetUi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetUser = $this->findTargetUserOrFail((int) $validated['user_id']);
        $request->session()->put(self::PROXY_USER_SESSION_KEY, (int) $targetUser->id);

        return redirect()->route('bet.races')->with('success', ($targetUser->display_name ?: $targetUser->name) . ' の代理購入モードで開始しました。');
    }

    public function stopBetUi(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PROXY_USER_SESSION_KEY);

        return redirect()->route('admin.proxy-entry.edit')->with('status', '代理購入モードを終了しました。');
    }

    public function storeAdjustment(
        Request $request,
        BetSettlementService $settlementService
    ): RedirectResponse {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'race_id' => ['required', 'integer', 'exists:races,id'],
            'bonus_points' => ['required', 'integer', 'between:-1000000,1000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $targetUser = $this->findTargetUserOrFail((int) $validated['user_id']);

        RaceUserAdjustment::query()->updateOrCreate(
            [
                'user_id' => $targetUser->id,
                'race_id' => (int) $validated['race_id'],
            ],
            [
                'bonus_points' => (int) $validated['bonus_points'],
                'note' => $validated['note'] ?? null,
            ]
        );

        $settlementService->recalculateForRace((int) $validated['race_id']);
        $settlementService->recalculateUserBalance((int) $targetUser->id);

        return $this->redirectWithSelection($validated)->with('status', 'ボーナスPTを更新しました。');
    }

    public function destroyRaceData(
        Request $request,
        BetSettlementService $settlementService
    ): RedirectResponse {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'race_id' => ['required', 'integer', 'exists:races,id'],
        ]);

        $targetUser = $this->findTargetUserOrFail((int) $validated['user_id']);
        $raceId = (int) $validated['race_id'];

        Bet::query()
            ->where('user_id', $targetUser->id)
            ->where('race_id', $raceId)
            ->delete();

        $adjustment = RaceUserAdjustment::query()
            ->where('user_id', $targetUser->id)
            ->where('race_id', $raceId)
            ->first();

        if ($adjustment !== null) {
            $adjustment->bonus_points = 0;
            $adjustment->challenge_choice = null;
            $adjustment->challenge_chosen_at = null;
            $adjustment->note = null;
            $adjustment->save();
        }

        $settlementService->recalculateForRace($raceId);
        $settlementService->recalculateUserBalance((int) $targetUser->id);

        return $this->redirectWithSelection($validated)->with('status', '対象レースの馬券・調整データを削除しました。');
    }

    private function findTargetUserOrFail(int $userId): User
    {
        $adminRoles = config('domain.roles.admin', ['admin', 'kannrisyato']);
        $user = User::query()->findOrFail($userId);
        if (in_array((string) $user->role, $adminRoles, true)) {
            throw ValidationException::withMessages([
                'user_id' => '管理者ユーザーは代理入力対象にできません。',
            ]);
        }

        return $user;
    }

    private function redirectWithSelection(array $values): RedirectResponse
    {
        return redirect()->route('admin.proxy-entry.edit', [
            'user_id' => (int) ($values['user_id'] ?? 0),
            'race_id' => (int) ($values['race_id'] ?? 0),
        ]);
    }
}
