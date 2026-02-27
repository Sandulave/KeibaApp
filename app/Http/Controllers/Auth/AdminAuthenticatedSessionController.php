<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.admin-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'name' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'name' => trans('auth.failed'),
            ]);
        }

        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'name' => '管理者アカウントのみログインできます。',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('races.index', absolute: false));
    }
}
