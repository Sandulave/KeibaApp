<x-guest-layout>
    <div class="mb-4 flex justify-center">
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-auto w-full max-w-xs" />
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <x-input-error :messages="$errors->get('discord')" class="mb-4" />

    <div class="mt-4">
        <p class="mb-3 text-sm text-gray-600">ログインはDiscordアカウントのみ対応しています。</p>
        <a
            href="{{ route('auth.discord.redirect', [], false) }}"
            class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
        >
            Discordでログイン
        </a>
        <a href="{{ route('admin.login', [], false) }}" class="mt-3 inline-block text-sm text-gray-600 hover:text-gray-900">
            管理者ログインはこちら
        </a>
    </div>
</x-guest-layout>
