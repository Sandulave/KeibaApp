<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $filter = (string)$request->query('role', 'all');
        $sortKey = (string) $request->query('sort', 'total_amount');
        $sortDir = (string) $request->query('dir', 'desc');
        $audienceMap = config('domain.audience_roles', [
            'streamer' => 'streamer',
            'viewer' => 'viewer',
        ]);
        $adminRoles = config('domain.roles.admin', ['admin', 'kannrisyato']);
        $viewerFallbackRole = (string) config('domain.roles.viewer_fallback', 'user');
        $allowedSortKeys = [
            'total_amount',
            'total_stake',
            'total_return',
            'roi_percent',
            'bonus_points',
            'carry_over_amount',
            'display_name',
        ];
        if (!in_array($sortKey, $allowedSortKeys, true)) {
            $sortKey = 'total_amount';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $query = Bet::query()
            ->select([
                'users.id as user_id',
                'users.name as user_name',
                'users.display_name as user_display_name',
                'users.role as user_role',
                'users.audience_role as user_audience_role',
                DB::raw('COUNT(bets.id) as bet_count'),
                DB::raw('COALESCE(SUM(bets.stake_amount), 0) as total_stake'),
                DB::raw('COALESCE(SUM(bets.return_amount), 0) as total_return'),
                DB::raw('COALESCE(SUM(bets.hit_count), 0) as total_hits'),
            ])
            ->join('users', 'users.id', '=', 'bets.user_id')
            ->whereNotIn('users.role', $adminRoles)
            ->groupBy('users.id', 'users.name', 'users.display_name', 'users.role', 'users.audience_role')
            ->orderByDesc(DB::raw('COALESCE(SUM(bets.return_amount), 0)'))
            ->orderBy('users.id');

        if (isset($audienceMap[$filter])) {
            $audienceRole = $audienceMap[$filter];
            $query->where(function ($q) use ($audienceRole, $adminRoles, $viewerFallbackRole) {
                $q->where('users.audience_role', $audienceRole);

                // 既存データ互換: audience_role が未設定なら旧 role で暫定判定
                if ($audienceRole === 'streamer') {
                    $q->orWhere(function ($qq) use ($adminRoles) {
                        $qq->whereNull('users.audience_role')
                            ->whereIn('users.role', $adminRoles);
                    });
                } elseif ($audienceRole === 'viewer') {
                    $q->orWhere(function ($qq) use ($viewerFallbackRole) {
                        $qq->whereNull('users.audience_role')
                            ->where('users.role', $viewerFallbackRole);
                    });
                }
            });
        }

        $adjustmentByUser = RaceUserAdjustment::query()
            ->select([
                'user_id',
                DB::raw('COALESCE(SUM(bonus_points), 0) as total_bonus_points'),
                DB::raw('COALESCE(SUM(carry_over_amount), 0) as total_carry_over_amount'),
            ])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $baseRows = $query
            ->get()
            ->map(function ($row) use ($adjustmentByUser) {
                $stake = (int)$row->total_stake;
                $return = (int)$row->total_return;
                $betCount = (int)$row->bet_count;
                $hitCount = (int)$row->total_hits;
                $adjustment = $adjustmentByUser->get($row->user_id);
                $bonusPoints = (int)($adjustment->total_bonus_points ?? 0);
                $carryOverAmount = (int)($adjustment->total_carry_over_amount ?? 0);
                $totalAdjustment = $bonusPoints + $carryOverAmount;

                $row->roi_percent = $stake > 0
                    ? round(($return / $stake) * 100, 2)
                    : null;
                $row->hit_rate_percent = $betCount > 0
                    ? round(($hitCount / $betCount) * 100, 2)
                    : null;
                $row->display_name = $row->user_display_name ?: $row->user_name;
                $row->audience_role_label = $this->audienceRoleLabel($row->user_audience_role, $row->user_role);
                $row->profit_amount = $return - $stake;
                $row->bonus_points = $bonusPoints;
                $row->carry_over_amount = $carryOverAmount;
                $row->total_amount = $return + $totalAdjustment;
                $row->total_score = $stake > 0
                    ? round((($return + $totalAdjustment) / $stake) * 100, 2)
                    : null;

                return $row;
            });

        $rankByUserId = $baseRows
            ->sort(fn ($a, $b) => $this->compareStatsRows($a, $b, 'total_amount', 'desc'))
            ->values()
            ->mapWithKeys(fn ($row, $index) => [(int) $row->user_id => $index + 1]);

        $rows = $baseRows
            ->sort(fn ($a, $b) => $this->compareStatsRows($a, $b, $sortKey, $sortDir))
            ->values();

        return view('stats.index', [
            'rows' => $rows,
            'rankByUserId' => $rankByUserId,
            'roleFilter' => $filter,
            'sortKey' => $sortKey,
            'sortDir' => $sortDir,
        ]);
    }

    public function show(User $user)
    {
        abort_if($user->isAdmin(), 404);
        $actor = request()->user();
        $isAdmin = $actor->isAdmin();
        $canEditAdjustments = $isAdmin || $actor->id === $user->id;
        $adjustmentMax = (int) config('domain.stats.adjustment_max', 1_000_000);

        $displayName = $user->display_name ?: $user->name;
        $audienceRoleLabel = $this->audienceRoleLabel($user->audience_role, $user->role);

        $summary = Bet::query()
            ->where('user_id', $user->id)
            ->selectRaw('COUNT(*) as bet_count')
            ->selectRaw('COALESCE(SUM(stake_amount), 0) as total_stake')
            ->selectRaw('COALESCE(SUM(return_amount), 0) as total_return')
            ->selectRaw('COALESCE(SUM(hit_count), 0) as total_hits')
            ->first();

        $totalStake = (int)($summary->total_stake ?? 0);
        $totalReturn = (int)($summary->total_return ?? 0);

        $overallRoi = $totalStake > 0
            ? round(($totalReturn / $totalStake) * 100, 2)
            : null;
        $bonusPoints = (int)(RaceUserAdjustment::where('user_id', $user->id)->sum('bonus_points'));
        $carryOverAmount = (int)(RaceUserAdjustment::where('user_id', $user->id)->sum('carry_over_amount'));
        $totalAdjustment = $bonusPoints + $carryOverAmount;
        $totalScore = $totalStake > 0
            ? round((($totalReturn + $totalAdjustment) / $totalStake) * 100, 2)
            : null;

        $raceRows = Bet::query()
            ->where('bets.user_id', $user->id)
            ->join('races', 'races.id', '=', 'bets.race_id')
            ->leftJoin('race_user_adjustments as rua', function ($join) use ($user) {
                $join->on('rua.race_id', '=', 'races.id')
                    ->where('rua.user_id', '=', $user->id);
            })
            ->select([
                'races.id as race_id',
                'races.name as race_name',
                'races.race_date as race_date',
                DB::raw('COUNT(bets.id) as bet_count'),
                DB::raw('COALESCE(SUM(bets.stake_amount), 0) as total_stake'),
                DB::raw('COALESCE(SUM(bets.return_amount), 0) as total_return'),
                DB::raw('COALESCE(SUM(bets.hit_count), 0) as total_hits'),
                DB::raw('COALESCE(MAX(rua.bonus_points), 0) as bonus_points'),
                DB::raw('COALESCE(MAX(rua.carry_over_amount), 0) as carry_over_amount'),
            ])
            ->groupBy('races.id', 'races.name', 'races.race_date')
            ->orderBy('races.race_date')
            ->orderBy('races.id')
            ->get()
            ->map(function ($row) {
                $stake = (int)$row->total_stake;
                $return = (int)$row->total_return;
                $bonus = (int)$row->bonus_points;
                $carryOver = (int)$row->carry_over_amount;
                $adj = $bonus + $carryOver;

                $row->roi_percent = $stake > 0
                    ? round(($return / $stake) * 100, 2)
                    : null;
                $row->total_score = $stake > 0
                    ? round((($return + $adj) / $stake) * 100, 2)
                    : null;
                $row->total_adjustment = $adj;

                return $row;
            });

        return view('stats.show', [
            'user' => $user,
            'displayName' => $displayName,
            'audienceRoleLabel' => $audienceRoleLabel,
            'totalStake' => $totalStake,
            'totalReturn' => $totalReturn,
            'overallRoi' => $overallRoi,
            'bonusPoints' => $bonusPoints,
            'carryOverAmount' => $carryOverAmount,
            'totalAdjustment' => $totalAdjustment,
            'totalScore' => $totalScore,
            'raceRows' => $raceRows,
            'canEditAdjustments' => $canEditAdjustments,
            'adjustmentMax' => $adjustmentMax,
        ]);
    }

    public function updateAdjustment(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 404);
        $actor = $request->user();
        $isAdmin = $actor->isAdmin();
        abort_unless($isAdmin || $actor->id === $user->id, 403);

        $adjustmentMax = (int) config('domain.stats.adjustment_max', 1_000_000);

        $validated = $request->validate([
            'race_id' => ['required', 'integer', 'exists:races,id'],
            'bonus_points' => ['required', 'integer', "between:-{$adjustmentMax},{$adjustmentMax}"],
            'carry_over_amount' => ['required', 'integer', "between:-{$adjustmentMax},{$adjustmentMax}", 'multiple_of:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'race_id.required' => '対象レースを指定してください。',
            'race_id.exists' => '対象レースが見つかりません。',
            'bonus_points.required' => 'ボーナスPTを入力してください。',
            'bonus_points.integer' => 'ボーナスPTは数値で入力してください。',
            'bonus_points.between' => "ボーナスPTは-{$adjustmentMax}〜{$adjustmentMax}の範囲で入力してください。",
            'carry_over_amount.required' => '繰越金を入力してください。',
            'carry_over_amount.integer' => '繰越金は数値で入力してください。',
            'carry_over_amount.between' => "繰越金は-{$adjustmentMax}〜{$adjustmentMax}の範囲で入力してください。",
            'carry_over_amount.multiple_of' => '繰越金は100円単位で入力してください。',
            'note.max' => 'メモは255文字以内で入力してください。',
        ]);

        RaceUserAdjustment::updateOrCreate(
            [
                'user_id' => $user->id,
                'race_id' => (int)$validated['race_id'],
            ],
            [
                'bonus_points' => (int)$validated['bonus_points'],
                'carry_over_amount' => (int)$validated['carry_over_amount'],
                'note' => $validated['note'] ?? null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'ボーナスPTと繰越金を更新しました。',
            ]);
        }

        return back()->with('success', 'ボーナスPTと繰越金を更新しました。');
    }

    public function raceBets(User $user, Race $race)
    {
        abort_if($user->isAdmin(), 404);

        $displayName = $user->display_name ?: $user->name;

        $bets = Bet::query()
            ->where('user_id', $user->id)
            ->where('race_id', $race->id)
            ->with(['items' => function ($q) {
                $q->orderBy('id');
            }])
            ->orderByDesc('bought_at')
            ->orderByDesc('id')
            ->get();

        $betTypeLabels = config('domain.bet.type_labels', []);

        return view('stats.race_bets', [
            'user' => $user,
            'race' => $race,
            'displayName' => $displayName,
            'bets' => $bets,
            'betTypeLabels' => $betTypeLabels,
        ]);
    }

    public function destroyAdjustment(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 404);
        $actor = $request->user();
        $isAdmin = $actor->isAdmin();
        abort_unless($isAdmin || $actor->id === $user->id, 403);

        $validated = $request->validate([
            'race_id' => ['required', 'integer', 'exists:races,id'],
        ], [
            'race_id.required' => '対象レースを指定してください。',
            'race_id.exists' => '対象レースが見つかりません。',
        ]);

        $raceId = (int) $validated['race_id'];

        DB::transaction(function () use ($user, $raceId) {
            Bet::query()
                ->where('user_id', $user->id)
                ->where('race_id', $raceId)
                ->delete();

            RaceUserAdjustment::query()
                ->where('user_id', $user->id)
                ->where('race_id', $raceId)
                ->delete();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => '対象レースの馬券・ボーナスPT・繰越金を削除しました。',
            ]);
        }

        return back()->with('success', '対象レースの馬券・ボーナスPT・繰越金を削除しました。');
    }

    private function audienceRoleLabel(?string $audienceRole, string $role): string
    {
        $adminRoles = config('domain.roles.admin', ['admin', 'kannrisyato']);
        $viewerFallbackRole = (string) config('domain.roles.viewer_fallback', 'user');

        return match ($audienceRole) {
            'streamer' => '配信者',
            'viewer' => '視聴者',
            default => in_array($role, $adminRoles, true) ? '配信者' : ($role === $viewerFallbackRole ? '視聴者' : '-'),
        };
    }

    private function compareStatsRows(object $a, object $b, string $sortKey, string $sortDir): int
    {
        $aValue = $sortKey === 'display_name'
            ? (string)($a->display_name ?? '')
            : (float)($a->{$sortKey} ?? 0);
        $bValue = $sortKey === 'display_name'
            ? (string)($b->display_name ?? '')
            : (float)($b->{$sortKey} ?? 0);

        if ($sortKey === 'display_name') {
            $cmp = strcasecmp($aValue, $bValue);
            if ($cmp !== 0) {
                return $sortDir === 'asc' ? $cmp : -$cmp;
            }
        } elseif ($aValue !== $bValue) {
            if ($sortDir === 'asc') {
                return $aValue < $bValue ? -1 : 1;
            }

            return $aValue > $bValue ? -1 : 1;
        }

        $aTotal = (int)($a->total_amount ?? 0);
        $bTotal = (int)($b->total_amount ?? 0);
        if ($aTotal !== $bTotal) {
            return $aTotal > $bTotal ? -1 : 1;
        }

        $aStake = (int)($a->total_stake ?? 0);
        $bStake = (int)($b->total_stake ?? 0);
        if ($aStake !== $bStake) {
            return $aStake < $bStake ? -1 : 1;
        }

        return (int)$a->user_id <=> (int)$b->user_id;
    }
}
