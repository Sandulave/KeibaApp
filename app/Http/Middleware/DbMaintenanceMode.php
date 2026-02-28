<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class DbMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('app_settings')) {
            return $next($request);
        }

        if (! AppSetting::maintenanceEnabled()) {
            return $next($request);
        }

        if ($request->routeIs('admin.login*')) {
            return $next($request);
        }

        $user = Auth::user();
        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        abort(503, AppSetting::maintenanceMessage() ?? '現在メンテナンス中です。');
    }
}
