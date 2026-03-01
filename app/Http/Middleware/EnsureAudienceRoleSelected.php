<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAudienceRoleSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('audience-role.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        $allowedAudienceRoles = array_values(config('domain.audience_roles', []));

        if (in_array($user->audience_role, $allowedAudienceRoles, true)) {
            return $next($request);
        }

        return redirect()->route('audience-role.edit');
    }
}
