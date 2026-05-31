<?php

use App\Http\Controllers\Api\JraVanSettlementImportController;
use Illuminate\Support\Facades\Route;

Route::middleware('jra_van.import')->group(function () {
    Route::post('/jra-van/races/{race}/settlement', JraVanSettlementImportController::class)
        ->name('api.jra-van.races.settlement.import');
});
