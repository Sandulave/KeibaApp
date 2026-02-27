<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'prompt' => 'none',
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
        $legacyName = 'discord_'.$discordId;
        $user = User::query()->where('discord_id', $discordId)->first();

        if (! $user) {
            $user = User::query()
                ->whereIn('name', [$discordId, $legacyName])
                ->first();
        }

        if ($user && $user->discord_id !== $discordId) {
            $user->forceFill(['discord_id' => $discordId])->save();
        }

        if (! $user) {
            $user = User::create([
                'name' => $discordId,
                'display_name' => $discordUser['global_name'] ?? $discordUser['username'] ?? null,
                'password' => Str::password(32),
                'discord_id' => $discordId,
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

}
