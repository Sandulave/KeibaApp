<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowedRoles = [];

        foreach ($roles as $role) {
            if (str_starts_with($role, 'group:')) {
                $group = substr($role, strlen('group:'));
                $groupRoles = config("domain.roles.groups.{$group}", []);
                if (is_array($groupRoles)) {
                    $allowedRoles = array_merge($allowedRoles, $groupRoles);
                }
                continue;
            }

            $allowedRoles[] = $role;
        }

        $allowedRoles = array_values(array_unique($allowedRoles));

        if (!$user || !in_array($user->role, $allowedRoles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
