<x-guest-layout>
    <div class="mb-4 flex justify-center">
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-auto w-full max-w-xs" />
    </div>

    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        <p class="font-semibold">初回ログインの確認</p>
        <p class="mt-2">このDiscordアカウントは本アプリに未登録のため、新規ユーザーとして登録されます。</p>
        @if (!empty($pendingDiscord['display_name']))
            <p class="mt-2">表示名: {{ $pendingDiscord['display_name'] }}</p>
        @endif
        <p class="mt-2">意図しないアカウントの場合はキャンセルして、別アカウントで再度ログインしてください。</p>
        <p class="mt-2">別アカウントが表示された場合は、ブラウザ版Discordから一度ログアウトし、正しいアカウントでログインし直してから再度お試しください。</p>
    </div>

    <div class="mt-4 flex flex-col gap-2">
        <form method="POST" action="{{ route('auth.discord.register.complete', [], false) }}">
            @csrf
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
            >
                新規登録して続行
            </button>
        </form>

        <form method="POST" action="{{ route('auth.discord.register.cancel', [], false) }}">
            @csrf
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-700"
            >
                キャンセルして戻る
            </button>
        </form>
    </div>
</x-guest-layout>
