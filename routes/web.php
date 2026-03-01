<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\AudienceRoleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\RacePayoutController;
use App\Http\Controllers\RaceSettlementController;
use App\Http\Controllers\BetFlowController;
use App\Http\Controllers\StatsController;

Route::get('/', fn() => redirect()->route('dashboard'));

// ログイン後の分岐
Route::get('/dashboard', function () {
    $user = auth()->user();

    return $user->isAdmin()
        ? redirect()->route('races.index')
        : redirect()->route('bet.races');
})->middleware(['auth', 'audience_role.selected'])->name('dashboard');

// 管理者だけ：レース管理
Route::middleware(['auth', 'role:group:admin'])->group(function () {
    Route::resource('races', RaceController::class);
    Route::get('/admin/maintenance', [MaintenanceController::class, 'edit'])->name('admin.maintenance.edit');
    Route::put('/admin/maintenance', [MaintenanceController::class, 'update'])->name('admin.maintenance.update');

    Route::prefix('races/{race}')
        ->name('races.')
        ->group(function () {
            Route::get('payouts', [RacePayoutController::class, 'index'])->name('payouts.index');
            Route::post('payouts', [RacePayoutController::class, 'store'])->name('payouts.store');
            Route::delete('payouts/{payout}', [RacePayoutController::class, 'destroy'])->name('payouts.destroy');

            Route::get('settlement/edit', [RaceSettlementController::class, 'edit'])->name('settlement.edit');
            Route::post('settlement', [RaceSettlementController::class, 'update'])->name('settlement.update');
        });
});

// 一般ユーザー（＋adminも閲覧OKなら含める）
Route::middleware(['auth', 'audience_role.selected', 'role:group:stats_access'])->group(function () {
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/stats/users/{user}', [StatsController::class, 'show'])->name('stats.users.show');
    Route::get('/stats/users/{user}/races/{race}/bets', [StatsController::class, 'raceBets'])->name('stats.users.race-bets');
    Route::post('/stats/users/{user}/adjustments', [StatsController::class, 'updateAdjustment'])->name('stats.users.adjustments.update');
    Route::delete('/stats/users/{user}/adjustments', [StatsController::class, 'destroyAdjustment'])->name('stats.users.adjustments.destroy');

    // レース選択
    Route::get('/bet', [BetFlowController::class, 'selectRace'])->name('bet.races');
    Route::get('/bet/{race}/challenge', [BetFlowController::class, 'selectChallenge'])->name('bet.challenge.select');
    Route::post('/bet/{race}/challenge', [BetFlowController::class, 'storeChallengeChoice'])->name('bet.challenge.store');

    // 券種選択（追加）
    Route::get('/bet/{race}/types', [BetFlowController::class, 'selectType'])->name('bet.types');

    // 買い方選択（追加）
    Route::get('/bet/{race}/types/{betType}/modes', [BetFlowController::class, 'selectMode'])->name('bet.modes');

    // 買い目入力（追加：betType + mode で画面を分岐）
    Route::get('/bet/{race}/types/{betType}/{mode}', [BetFlowController::class, 'buildByMode'])->name('bet.build.mode');
    // カート追加（既存）
    Route::post('/bet/{race}/cart/add', [BetFlowController::class, 'cartAdd'])->name('bet.cart.add');

    // カート表示＆操作（既存）
    Route::get('/bet/{race}/cart', [BetFlowController::class, 'cart'])->name('bet.cart');
    Route::post('/bet/{race}/cart/update', [BetFlowController::class, 'cartUpdate'])->name('bet.cart.update');

    // 確定（DB登録）（既存）
    Route::post('/bet/{race}/commit', [BetFlowController::class, 'commit'])->name('bet.commit');
});


// Breezeのプロフィール系
Route::middleware('auth')->group(function () {
    Route::get('/audience-role', [AudienceRoleController::class, 'edit'])->name('audience-role.edit');
    Route::put('/audience-role', [AudienceRoleController::class, 'update'])->name('audience-role.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



require __DIR__ . '/auth.php';
