<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use App\Services\Finance\BetMoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function __construct(
        private readonly BetMoneyService $betMoneyService
    ) {
    }

    public function index(Request $request)
    {
        $viewMode = (string) $request->query('view', 'user');
        if (!in_array($viewMode, ['user', 'race'], true)) {
            $viewMode = 'user';
        }

        $filter = (string)$request->query('role', 'all');
        $sortKey = (string) $request->query('sort', 'total_amount');
        $sortDir = (string) $request->query('dir', 'desc');
        $selectedRaceId = (int) $request->query('race_id', 0);
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
            'display_name',
            'allowance_amount',
        ];
        if (!in_array($sortKey, $allowedSortKeys, true)) {
            $sortKey = 'total_amount';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $raceOptions = Race::query()
            ->select(['id', 'name', 'race_date'])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('bets')
                    ->whereColumn('bets.race_id', 'races.id');
            })
            ->orderByDesc('race_date')
            ->orderByDesc('id')
            ->get();

        if ($viewMode === 'race' && $selectedRaceId <= 0 && $raceOptions->isNotEmpty()) {
            $selectedRaceId = (int) $raceOptions->first()->id;
        }

        $selectedRace = $viewMode === 'race' && $selectedRaceId > 0
            ? $raceOptions->firstWhere('id', $selectedRaceId)
            : null;

        if ($viewMode === 'race') {
            if ($selectedRace !== null) {
                $query = Bet::query()
                    ->select([
                        'users.id as user_id',
                        'users.name as user_name',
                        'users.display_name as user_display_name',
                        'users.current_balance as current_balance',
                        'users.role as user_role',
                        'users.audience_role as user_audience_role',
                        DB::raw('COUNT(bets.id) as bet_count'),
                        DB::raw('COALESCE(SUM(bets.stake_amount), 0) as total_stake'),
                        DB::raw('COALESCE(SUM(bets.return_amount), 0) as total_return'),
                        DB::raw('COALESCE(SUM(bets.hit_count), 0) as total_hits'),
                        DB::raw('COALESCE(MAX(rua.bonus_points), 0) as bonus_points'),
                        DB::raw('MAX(rua.challenge_choice) as challenge_choice'),
                    ])
                    ->join('users', 'users.id', '=', 'bets.user_id')
                    ->leftJoin('race_user_adjustments as rua', function ($join) use ($selectedRaceId) {
                        $join->on('rua.user_id', '=', 'users.id')
                            ->where('rua.race_id', '=', $selectedRaceId);
                    })
                    ->where('bets.race_id', $selectedRaceId)
                    ->whereNotIn('users.role', $adminRoles)
                    ->groupBy('users.id', 'users.name', 'users.display_name', 'users.current_balance', 'users.role', 'users.audience_role')
                    ->orderByDesc(DB::raw('COALESCE(SUM(bets.return_amount), 0)'))
                    ->orderBy('users.id');

                if (isset($audienceMap[$filter])) {
                    $audienceRole = $audienceMap[$filter];
                    $query->where(function ($q) use ($audienceRole, $adminRoles, $viewerFallbackRole) {
                        $q->where('users.audience_role', $audienceRole);

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

                $baseRows = $query
                    ->get()
                    ->map(function ($row) {
                        $stake = (int)$row->total_stake;
                        $return = (int)$row->total_return;
                        $betCount = (int)$row->bet_count;
                        $hitCount = (int)$row->total_hits;
                        $bonusPoints = (int)($row->bonus_points ?? 0);
                        $allowanceAmount = $this->betMoneyService->allowanceForChoice($row->challenge_choice);

                        $row->roi_percent = $this->betMoneyService->roiPercent($stake, $return);
                        $row->hit_rate_percent = $betCount > 0
                            ? round(($hitCount / $betCount) * 100, 2)
                            : null;
                        $row->display_name = $row->user_display_name ?: $row->user_name;
                        $row->audience_role_label = $this->audienceRoleLabel($row->user_audience_role, $row->user_role);
                        $row->profit_amount = $this->betMoneyService->profitAmount($stake, $return, $bonusPoints);
                        $row->bonus_points = $bonusPoints;
                        $row->allowance_amount = $allowanceAmount;
                        $row->total_amount = $row->profit_amount;
                        $row->total_score = $stake > 0
                            ? round(($row->total_amount / $stake) * 100, 2)
                            : null;

                        return $row;
                    });
            } else {
                $baseRows = collect();
            }
        } else {
            $query = Bet::query()
                ->select([
                    'users.id as user_id',
                    'users.name as user_name',
                    'users.display_name as user_display_name',
                    'users.current_balance as current_balance',
                    'users.role as user_role',
                    'users.audience_role as user_audience_role',
                    DB::raw('COUNT(bets.id) as bet_count'),
                    DB::raw('COALESCE(SUM(bets.stake_amount), 0) as total_stake'),
                    DB::raw('COALESCE(SUM(bets.return_amount), 0) as total_return'),
                    DB::raw('COALESCE(SUM(bets.hit_count), 0) as total_hits'),
                ])
                ->join('users', 'users.id', '=', 'bets.user_id')
                ->whereNotIn('users.role', $adminRoles)
                ->groupBy('users.id', 'users.name', 'users.display_name', 'users.current_balance', 'users.role', 'users.audience_role')
                ->orderByDesc(DB::raw('COALESCE(SUM(bets.return_amount), 0)'))
                ->orderBy('users.id');

            if (isset($audienceMap[$filter])) {
                $audienceRole = $audienceMap[$filter];
                $query->where(function ($q) use ($audienceRole, $adminRoles, $viewerFallbackRole) {
                    $q->where('users.audience_role', $audienceRole);

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
                    DB::raw("COALESCE(SUM(CASE challenge_choice WHEN 'challenge' THEN 30000 WHEN 'normal' THEN 10000 ELSE 0 END), 0) as total_allowance_amount"),
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
                    $allowanceAmount = (int)($adjustment->total_allowance_amount ?? 0);
                    $totalAdjustment = $bonusPoints;

                    $row->roi_percent = $this->betMoneyService->roiPercent($stake, $return);
                    $row->hit_rate_percent = $betCount > 0
                        ? round(($hitCount / $betCount) * 100, 2)
                        : null;
                    $row->display_name = $row->user_display_name ?: $row->user_name;
                    $row->audience_role_label = $this->audienceRoleLabel($row->user_audience_role, $row->user_role);
                    $row->profit_amount = $this->betMoneyService->profitAmount($stake, $return);
                    $row->bonus_points = $bonusPoints;
                    $row->allowance_amount = $allowanceAmount;
                    $row->total_amount = (int) ($row->current_balance ?? 0);
                    $row->total_score = $stake > 0
                        ? round(($row->total_amount / $stake) * 100, 2)
                        : null;

                    return $row;
                });
        }

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
            'viewMode' => $viewMode,
            'roleFilter' => $filter,
            'sortKey' => $sortKey,
            'sortDir' => $sortDir,
            'raceOptions' => $raceOptions,
            'selectedRaceId' => $selectedRaceId,
            'selectedRace' => $selectedRace,
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
        $totalAdjustment = $bonusPoints;
        $totalScore = $totalStake > 0
            ? round((($totalReturn + $totalAdjustment) / $totalStake) * 100, 2)
            : null;

        $raceRows = Race::query()
            ->leftJoin('bets', function ($join) use ($user) {
                $join->on('bets.race_id', '=', 'races.id')
                    ->where('bets.user_id', '=', $user->id);
            })
            ->leftJoin('race_user_adjustments as rua', function ($join) use ($user) {
                $join->on('rua.race_id', '=', 'races.id')
                    ->where('rua.user_id', '=', $user->id);
            })
            ->where(function ($q) {
                $q->whereNotNull('bets.id')
                    ->orWhereNotNull('rua.challenge_choice');
            })
            ->select([
                'races.id as race_id',
                'races.name as race_name',
                'races.race_date as race_date',
                'races.is_betting_closed as is_betting_closed',
                DB::raw('COUNT(bets.id) as bet_count'),
                DB::raw('COALESCE(SUM(bets.stake_amount), 0) as total_stake'),
                DB::raw('COALESCE(SUM(bets.return_amount), 0) as total_return'),
                DB::raw('COALESCE(SUM(bets.hit_count), 0) as total_hits'),
                DB::raw('COALESCE(MAX(rua.bonus_points), 0) as bonus_points'),
                DB::raw('MAX(rua.challenge_choice) as challenge_choice'),
            ])
            ->groupBy('races.id', 'races.name', 'races.race_date', 'races.is_betting_closed')
            ->orderBy('races.race_date')
            ->orderBy('races.id')
            ->get()
            ->map(function ($row) {
                $stake = (int)$row->total_stake;
                $return = (int)$row->total_return;
                $bonus = (int)$row->bonus_points;
                $adj = $bonus;

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
            'currentBalance' => (int) ($user->current_balance ?? 0),
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
            'challenge_choice' => ['nullable', 'in:normal,challenge'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'race_id.required' => '対象レースを指定してください。',
            'race_id.exists' => '対象レースが見つかりません。',
            'bonus_points.required' => 'ボーナスPTを入力してください。',
            'bonus_points.integer' => 'ボーナスPTは数値で入力してください。',
            'bonus_points.between' => "ボーナスPTは-{$adjustmentMax}〜{$adjustmentMax}の範囲で入力してください。",
            'challenge_choice.in' => '勝負レースの選択が不正です。',
            'note.max' => 'メモは255文字以内で入力してください。',
        ]);

        DB::transaction(function () use ($validated, $user) {
            $raceId = (int) $validated['race_id'];
            $updateValues = [
                'bonus_points' => (int) $validated['bonus_points'],
                'note' => $validated['note'] ?? null,
            ];

            $existingAdjustment = RaceUserAdjustment::query()
                ->where('user_id', $user->id)
                ->where('race_id', $raceId)
                ->lockForUpdate()
                ->first();
            $oldChoice = $existingAdjustment?->challenge_choice;
            $oldBonusPoints = (int) ($existingAdjustment?->bonus_points ?? 0);

            if (array_key_exists('challenge_choice', $validated)) {
                $updateValues['challenge_choice'] = $validated['challenge_choice'] !== null && $validated['challenge_choice'] !== ''
                    ? (string) $validated['challenge_choice']
                    : null;
                $updateValues['challenge_chosen_at'] = $updateValues['challenge_choice'] !== null ? now() : null;
            }

            $updatedAdjustment = RaceUserAdjustment::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'race_id' => $raceId,
                ],
                $updateValues
            );

            $newChoice = $updatedAdjustment->challenge_choice;
            $choiceDelta = $this->betMoneyService->challengeChoiceDelta($oldChoice, $newChoice);
            $newBonusPoints = (int) ($updatedAdjustment->bonus_points ?? 0);
            $bonusDelta = $newBonusPoints - $oldBonusPoints;
            $delta = $choiceDelta + $bonusDelta;

            if ($delta !== 0) {
                $targetUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->first();
                if ($targetUser !== null) {
                    $targetUser->current_balance = (int) ($targetUser->current_balance ?? 0) + $delta;
                    $targetUser->save();
                }
            }
        });

        if ($request->expectsJson()) {
            $freshUser = User::query()->find($user->id);
            return response()->json([
                'ok' => true,
                'message' => 'ボーナスPT・勝負レースを更新しました。',
                'current_balance' => (int) ($freshUser?->current_balance ?? 0),
            ]);
        }

        return back()->with('success', 'ボーナスPT・勝負レースを更新しました。');
    }

    public function raceBets(User $user, Race $race)
    {
        abort_if($user->isAdmin(), 404);

        $displayName = $user->display_name ?: $user->name;
        $race->loadMissing(['resultEntries', 'withdrawals', 'payouts']);

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
        $bets->each(function (Bet $bet) use ($betTypeLabels) {
            $bet->snapshot_text = $this->formatBuildSnapshotText($bet->build_snapshot, $betTypeLabels);
        });
        $resultByRank = collect([1, 2, 3])->mapWithKeys(
            fn (int $rank) => [$rank => $race->resultEntries->where('rank', $rank)->sortBy('horse_no')->pluck('horse_no')->all()]
        );
        $withdrawalHorses = $race->withdrawals->sortBy('horse_no')->pluck('horse_no')->all();
        $payoutsByBetType = $race->payouts
            ->groupBy('bet_type')
            ->map(fn ($rows) => $rows
                ->sortBy(fn ($row) => sprintf(
                    '%05d_%s',
                    (int) ($row->popularity ?? 99999),
                    (string) $row->selection_key
                ))
                ->values())
            ->sortKeys();

        return view('stats.race_bets', [
            'user' => $user,
            'race' => $race,
            'displayName' => $displayName,
            'bets' => $bets,
            'betTypeLabels' => $betTypeLabels,
            'resultByRank' => $resultByRank,
            'withdrawalHorses' => $withdrawalHorses,
            'payoutsByBetType' => $payoutsByBetType,
        ]);
    }

    private function formatBuildSnapshotText(mixed $snapshot, array $betTypeLabels): ?string
    {
        if (!is_array($snapshot)) {
            return null;
        }

        $groups = $snapshot['groups'] ?? null;
        if (!is_array($groups) || empty($groups)) {
            return null;
        }

        $blocks = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $betType = (string) ($group['bet_type'] ?? '');
            $mode = (string) ($group['mode'] ?? '');
            $input = is_array($group['input'] ?? null) ? $group['input'] : [];
            $pointCount = (int) ($group['point_count'] ?? 0);
            $unitAmount = isset($group['unit_amount']) ? (int) $group['unit_amount'] : null;
            $totalAmount = (int) ($group['total_amount'] ?? 0);
            if ($betType === '' || $pointCount <= 0) {
                continue;
            }

            $title = '■ ' . ($betTypeLabels[$betType] ?? $betType) . ' ' . $this->modeLabel($mode);
            $detailLines = $this->snapshotDetailLines($betType, $mode, $input);
            $amountLine = $unitAmount !== null
                ? "◇{$pointCount}点 各" . number_format($unitAmount) . ' (計' . number_format($totalAmount) . ')'
                : "◇{$pointCount}点 合計" . number_format($totalAmount);

            $blocks[] = trim(implode("\n", array_filter([
                $title,
                ...$detailLines,
                $amountLine,
            ])));
        }

        $removedItems = collect($snapshot['removed_items'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) use ($betTypeLabels) {
                $betType = (string) ($row['bet_type'] ?? '');
                $selectionKey = (string) ($row['selection_key'] ?? '');
                $amount = (int) ($row['amount'] ?? 0);

                if ($betType === '' || $selectionKey === '') {
                    return null;
                }

                $label = $betTypeLabels[$betType] ?? $betType;
                return "・{$label} {$selectionKey} " . number_format($amount) . '円';
            })
            ->filter()
            ->values()
            ->all();

        if (!empty($removedItems)) {
            $blocks[] = "■ 削除した買い目\n" . implode("\n", $removedItems);
        }

        $amountChanges = collect($snapshot['amount_changes'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row) use ($betTypeLabels) {
                $betType = (string) ($row['bet_type'] ?? '');
                $selectionKey = (string) ($row['selection_key'] ?? '');
                $oldAmount = (int) ($row['old_amount'] ?? 0);
                $newAmount = (int) ($row['new_amount'] ?? 0);

                if ($betType === '' || $selectionKey === '' || $oldAmount === $newAmount) {
                    return null;
                }

                $label = $betTypeLabels[$betType] ?? $betType;

                return '・' . $label . ' ' . $selectionKey . ' '
                    . number_format($oldAmount) . '円 → ' . number_format($newAmount) . '円';
            })
            ->filter()
            ->values()
            ->all();

        if (!empty($amountChanges)) {
            $blocks[] = "■ 金額変更\n" . implode("\n", $amountChanges);
        }

        return empty($blocks) ? null : implode("\n\n", $blocks);
    }

    private function snapshotDetailLines(string $betType, string $mode, array $input): array
    {
        $line = function (mixed $value): string {
            if (is_array($value)) {
                return empty($value) ? '-' : implode(', ', $value);
            }
            if (is_string($value) && $value !== '') {
                return $value;
            }
            return '-';
        };

        if (!empty($input['selection_keys']) && is_array($input['selection_keys'])) {
            return ['・' . implode(', ', $input['selection_keys'])];
        }

        if ($mode === 'box' || $mode === 'single') {
            $vals = $input['horses'] ?? $input['horse'] ?? $input['frames'] ?? [];
            return ['・' . $line($vals)];
        }

        if ($mode === 'formation') {
            if (isset($input['third'])) {
                return ['・' . $line($input['first'] ?? []) . ' - ' . $line($input['second'] ?? []) . ' - ' . $line($input['third'] ?? [])];
            }
            return ['・' . $line($input['first'] ?? []) . ' - ' . $line($input['second'] ?? [])];
        }

        if (in_array($mode, ['nagashi_1axis', 'nagashi_1axis_multi', 'oneaxis_multi'], true)) {
            $axis = $line($input['axis'] ?? '-');
            $opp = $line($input['opponents'] ?? []);
            $lines = ["・{$axis} - {$opp}"];
            if (in_array($betType, ['umatan'], true) && in_array($mode, ['nagashi_1axis_multi', 'oneaxis_multi'], true)) {
                $lines[] = "・{$opp} - {$axis}";
            }
            return $lines;
        }

        if ($mode === 'nagashi_2axis') {
            return ['・' . $line($input['axis1'] ?? '-') . ' - ' . $line($input['axis2'] ?? '-') . ' - ' . $line($input['opponents'] ?? [])];
        }

        return [];
    }

    private function modeLabel(string $mode): string
    {
        return match ($mode) {
            'single' => 'シングル',
            'box' => 'ボックス',
            'formation' => 'フォーメーション',
            'nagashi_1axis' => '1頭軸流し',
            'nagashi_1axis_multi', 'oneaxis_multi' => '1頭軸流し マルチ',
            'nagashi_2axis' => '2頭軸流し',
            default => $mode !== '' ? $mode : '買い方',
        };
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
        $isBettingClosed = Race::query()
            ->whereKey($raceId)
            ->value('is_betting_closed');

        if ($isBettingClosed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => '投票終了レースのため削除できません。',
                ], 422);
            }

            return back()->withErrors([
                'adjustment' => '投票終了レースのため削除できません。',
            ]);
        }

        DB::transaction(function () use ($user, $raceId) {
            $targetUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();

            $stakeTotal = (int) Bet::query()
                ->where('user_id', $user->id)
                ->where('race_id', $raceId)
                ->sum('stake_amount');

            $adjustment = RaceUserAdjustment::query()
                ->where('user_id', $user->id)
                ->where('race_id', $raceId)
                ->lockForUpdate()
                ->first();
            $bonusPointsDelta = (int) ($adjustment?->bonus_points ?? 0);
            $challengeChoice = $adjustment?->challenge_choice;
            $allowanceDelta = -$this->betMoneyService->allowanceForChoice($challengeChoice);

            Bet::query()
                ->where('user_id', $user->id)
                ->where('race_id', $raceId)
                ->delete();

            if ($adjustment !== null) {
                $adjustment->bonus_points = 0;
                $adjustment->challenge_choice = null;
                $adjustment->challenge_chosen_at = null;
                $adjustment->note = null;
                $adjustment->save();
            }

            if ($targetUser !== null) {
                $targetUser->current_balance = (int) ($targetUser->current_balance ?? 0)
                    + $stakeTotal
                    + $allowanceDelta
                    - $bonusPointsDelta;
                $targetUser->save();
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => '対象レースの馬券・ボーナスPTを削除しました。',
            ]);
        }

        return back()->with('success', '対象レースの馬券・ボーナスPTを削除しました。');
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
