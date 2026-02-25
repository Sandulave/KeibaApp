<x-guest-layout>
    <div class="mb-4 flex justify-center">
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-auto w-full max-w-xs" />
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login', [], false) }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" value="ユーザー名" />
            <x-text-input
                id="name"
                class="block mt-1 w-full"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="パスワード" />
            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    name="remember"
                >
                <span class="ms-2 text-sm text-gray-600">ログイン状態を保持</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-6">
            {{-- パスワード再発行はメール前提なので消しました --}}
            <x-primary-button class="ms-3">
                ログイン
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
