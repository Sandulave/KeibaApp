<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DiscordAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('discord_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.discord.client_id'),
            'redirect_uri' => config('services.discord.redirect'),
            'response_type' => 'code',
            'scope' => 'identify',
            'state' => $state,
            'prompt' => config('services.discord.prompt', 'consent'),
        ]);

        return redirect('https://discord.com/oauth2/authorize?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $request->has('code') || ! $request->has('state')) {
            return redirect()->route('login')->withErrors([
                'discord' => 'Discordログインに失敗しました。もう一度お試しください。',
            ]);
        }

        if ($request->string('state')->value() !== $request->session()->pull('discord_oauth_state')) {
            return redirect()->route('login')->withErrors([
                'discord' => 'Discordログインの検証に失敗しました。',
            ]);
        }

        $tokenResponse = Http::asForm()->post('https://discord.com/api/oauth2/token', [
            'client_id' => config('services.discord.client_id'),
            'client_secret' => config('services.discord.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $request->string('code')->value(),
            'redirect_uri' => config('services.discord.redirect'),
        ]);

        if (! $tokenResponse->ok() || ! isset($tokenResponse['access_token'])) {
            Log::warning('Discord token exchange failed', ['body' => $tokenResponse->json()]);

            return redirect()->route('login')->withErrors([
                'discord' => 'Discordトークンの取得に失敗しました。',
            ]);
        }

        $discordUserResponse = Http::withToken($tokenResponse['access_token'])
            ->get('https://discord.com/api/users/@me');

        if (! $discordUserResponse->ok() || ! isset($discordUserResponse['id'])) {
            Log::warning('Discord user fetch failed', ['body' => $discordUserResponse->json()]);

            return redirect()->route('login')->withErrors([
                'discord' => 'Discordユーザー情報の取得に失敗しました。',
            ]);
        }

        $discordUser = $discordUserResponse->json();
        $discordId = (string) $discordUser['id'];
        $user = $this->findUserByDiscordId($discordId);

        if ($user && $user->discord_id !== $discordId) {
            $user->forceFill(['discord_id' => $discordId])->save();
        }

        if (! $user) {
            $request->session()->put('pending_discord_registration', [
                'discord_id' => $discordId,
                'display_name' => $discordUser['global_name'] ?? $discordUser['username'] ?? null,
            ]);

            return redirect()->route('auth.discord.register.notice');
        }

        return $this->loginAndRedirect($request, $user);
    }

    public function showRegistrationNotice(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('pending_discord_registration');

        if (! is_array($pending) || ! isset($pending['discord_id'])) {
            return redirect()->route('login');
        }

        return view('auth.discord-register-notice', [
            'pendingDiscord' => $pending,
        ]);
    }

    public function completeRegistration(Request $request): RedirectResponse
    {
        $pending = $request->session()->pull('pending_discord_registration');

        if (! is_array($pending) || ! isset($pending['discord_id'])) {
            return redirect()->route('login')->withErrors([
                'discord' => 'Discordログインの確認情報が見つかりません。もう一度お試しください。',
            ]);
        }

        $discordId = (string) $pending['discord_id'];
        $user = $this->findUserByDiscordId($discordId);

        if ($user && $user->discord_id !== $discordId) {
            $user->forceFill(['discord_id' => $discordId])->save();
        }

        if (! $user) {
            $user = User::create([
                'name' => $discordId,
                'display_name' => $pending['display_name'] ?? null,
                'password' => Str::password(32),
                'discord_id' => $discordId,
            ]);
        }

        return $this->loginAndRedirect($request, $user);
    }

    public function cancelRegistration(Request $request): RedirectResponse
    {
        $request->session()->forget('pending_discord_registration');

        return redirect()->route('login')->with('status', 'Discordログインをキャンセルしました。');
    }

    private function findUserByDiscordId(string $discordId): ?User
    {
        $legacyName = 'discord_'.$discordId;
        $user = User::query()->where('discord_id', $discordId)->first();

        if ($user) {
            return $user;
        }

        return User::query()
            ->whereIn('name', [$discordId, $legacyName])
            ->first();
    }

    private function loginAndRedirect(Request $request, User $user): RedirectResponse
    {
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
