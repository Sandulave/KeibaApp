<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\BetSettlementService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('balances:recalculate {--dry-run : Show differences without updating users}', function (BetSettlementService $settlementService) {
    $dbConnection = config('database.default');
    $dbConfig = config("database.connections.{$dbConnection}", []);
    $this->table(['App Env', 'DB Connection', 'DB Host', 'DB Name'], [[
        (string) config('app.env'),
        (string) $dbConnection,
        (string) ($dbConfig['host'] ?? '(none)'),
        (string) ($dbConfig['database'] ?? '(none)'),
    ]]);

    $users = User::query()
        ->orderBy('id')
        ->get(['id', 'name', 'display_name', 'discord_id', 'role', 'current_balance']);
    $expectedBalances = $settlementService->expectedCurrentBalances($users->pluck('id')->all());

    $diffRows = $users
        ->map(function (User $user) use ($expectedBalances) {
            $current = (int) ($user->current_balance ?? 0);
            $expected = (int) ($expectedBalances[$user->id] ?? 0);

            return [
                'id' => $user->id,
                'display_name' => $user->display_name ?: '(未設定)',
                'login_id' => $user->name,
                'role' => $user->role,
                'current' => $current,
                'expected' => $expected,
                'diff' => $expected - $current,
            ];
        })
        ->filter(fn (array $row) => $row['diff'] !== 0)
        ->values();

    if ($diffRows->isEmpty()) {
        $this->info('All user balances are already correct.');
        return 0;
    }

    $this->table(['ID', 'Display Name', 'Login ID', 'Role', 'Current', 'Expected', 'Diff'], $diffRows->all());

    $diffUserIds = $diffRows->pluck('id')->all();
    $betRows = DB::table('bets')
        ->join('races', 'races.id', '=', 'bets.race_id')
        ->whereIn('bets.user_id', $diffUserIds)
        ->selectRaw('bets.user_id, races.id as race_id, races.name as race_name, races.race_date, COALESCE(SUM(bets.stake_amount), 0) as stake, COALESCE(SUM(bets.return_amount), 0) as return_amount')
        ->groupBy('bets.user_id', 'races.id', 'races.name', 'races.race_date')
        ->get()
        ->keyBy(fn ($row) => $row->user_id.'|'.$row->race_id);
    $adjustmentRows = DB::table('race_user_adjustments')
        ->join('races', 'races.id', '=', 'race_user_adjustments.race_id')
        ->whereIn('race_user_adjustments.user_id', $diffUserIds)
        ->selectRaw("race_user_adjustments.user_id, races.id as race_id, races.name as race_name, races.race_date, COALESCE(SUM(race_user_adjustments.bonus_points), 0) as bonus_points, COALESCE(SUM(COALESCE(race_user_adjustments.granted_allowance, CASE race_user_adjustments.challenge_choice WHEN 'challenge' THEN 30000 WHEN 'normal' THEN 10000 ELSE 0 END)), 0) as allowance")
        ->groupBy('race_user_adjustments.user_id', 'races.id', 'races.name', 'races.race_date')
        ->get()
        ->keyBy(fn ($row) => $row->user_id.'|'.$row->race_id);

    $detailKeys = $betRows->keys()
        ->merge($adjustmentRows->keys())
        ->unique()
        ->sort()
        ->values();
    $detailRows = $detailKeys
        ->map(function (string $key) use ($betRows, $adjustmentRows, $users) {
            $betRow = $betRows->get($key);
            $adjustmentRow = $adjustmentRows->get($key);
            $row = $betRow ?? $adjustmentRow;
            $user = $users->firstWhere('id', (int) $row->user_id);
            $stake = (int) ($betRow->stake ?? 0);
            $return = (int) ($betRow->return_amount ?? 0);
            $bonus = (int) ($adjustmentRow->bonus_points ?? 0);
            $allowance = (int) ($adjustmentRow->allowance ?? 0);

            return [
                'user_id' => (int) $row->user_id,
                'display_name' => $user?->display_name ?: '(未設定)',
                'race_id' => (int) $row->race_id,
                'race_date' => (string) $row->race_date,
                'race_name' => (string) $row->race_name,
                'stake' => $stake,
                'return' => $return,
                'bonus' => $bonus,
                'allowance' => $allowance,
                'total' => $return - $stake + $bonus + $allowance,
            ];
        })
        ->values();

    if ($detailRows->isNotEmpty()) {
        $this->line('Race breakdown for users with balance differences:');
        foreach ($detailRows as $detailRow) {
            $this->line(sprintf(
                'User #%d %s / Race #%d %s %s: return %d - stake %d + bonus %d + allowance %d = %d',
                $detailRow['user_id'],
                $detailRow['display_name'],
                $detailRow['race_id'],
                $detailRow['race_date'],
                $detailRow['race_name'],
                $detailRow['return'],
                $detailRow['stake'],
                $detailRow['bonus'],
                $detailRow['allowance'],
                $detailRow['total'],
            ));
        }
        $this->table(
            ['User ID', 'Display Name', 'Race ID', 'Race Date', 'Race Name', 'Stake', 'Return', 'Bonus', 'Allowance', 'Total'],
            $detailRows->all()
        );
    }

    if ($this->option('dry-run')) {
        $this->warn('Dry run only. No balances were updated.');
        return 0;
    }

    $settlementService->recalculateAllUserBalances();
    $this->info("Updated {$diffRows->count()} user balance(s).");

    return 0;
})->purpose('Rebuild user current balances from bets, payouts, allowances, and adjustments');
