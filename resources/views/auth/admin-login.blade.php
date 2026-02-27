<x-guest-layout>
    <div class="mb-4 flex justify-center">
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-auto w-full max-w-xs" />
    </div>

    <h1 class="mb-4 text-lg font-semibold text-gray-800">管理者ログイン</h1>

    <form method="POST" action="{{ route('admin.login.store', [], false) }}">
        @csrf

        <div>
            <x-input-label for="name" value="管理者ID" />
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

        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('login', [], false) }}" class="text-sm text-gray-600 hover:text-gray-900">一般ログインへ</a>
            <x-primary-button>
                ログイン
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
