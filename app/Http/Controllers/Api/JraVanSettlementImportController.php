<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RaceSettlementRequest;
use App\Models\Race;
use App\Services\BetSettlementService;
use App\Services\RaceSettlementWriter;
use Illuminate\Http\JsonResponse;

class JraVanSettlementImportController extends Controller
{
    public function __invoke(
        RaceSettlementRequest $request,
        Race $race,
        RaceSettlementWriter $settlementWriter,
        BetSettlementService $settlementService
    ): JsonResponse {
        $settlementWriter->replaceAll($race, $request->validated());
        $settlementService->recalculateForRace($race->id);

        return response()->json([
            'message' => 'imported',
            'race_id' => $race->id,
        ]);
    }
}
