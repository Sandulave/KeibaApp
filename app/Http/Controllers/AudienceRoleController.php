<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AudienceRoleController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return redirect()->route('dashboard');
        }

        return view('auth.audience-role', [
            'currentAudienceRole' => $user->audience_role,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return redirect()->route('dashboard');
        }

        $audienceRoles = array_values(config('domain.audience_roles', []));

        $validated = $request->validate([
            'audience_role' => ['required', 'string', 'in:'.implode(',', $audienceRoles)],
        ]);

        $user->forceFill([
            'audience_role' => $validated['audience_role'],
        ])->save();

        return redirect()->route('dashboard');
    }
}
