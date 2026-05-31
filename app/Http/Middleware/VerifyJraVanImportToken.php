<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyJraVanImportToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.jra_van_import.token', '');
        if ($expectedToken === '') {
            return response()->json([
                'message' => 'JRA-VAN import token is not configured.',
            ], 503);
        }

        $providedToken = $request->bearerToken()
            ?: (string) $request->header('X-JRA-VAN-IMPORT-TOKEN', '');

        if (!hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
