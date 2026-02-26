<x-app-layout title="勝負レース選択">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">勝負レース宣言の選択</h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $race->race_date }} / {{ $race->name }}
            </p>
        </div>

        @if (session('error'))
            <div class="rounded bg-red-100 p-3 text-red-800">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded bg-red-100 p-3 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl bg-white p-6 ring-1 ring-gray-200 space-y-4">
            <p class="text-sm text-gray-700">
                このレースを勝負レースにするか選択してください。
            </p>

            <form method="POST" action="{{ route('bet.challenge.store', $race) }}" class="space-y-3">
                @csrf
                <button type="submit"
                    name="challenge_choice"
                    value="challenge"
                    class="w-full rounded-lg bg-amber-500 px-4 py-3 text-white font-semibold hover:bg-amber-600"
                    onclick="return confirm('このレースを勝負レースに設定しますか？');">
                    勝負レースにする
                </button>

                <button type="submit"
                    name="challenge_choice"
                    value="normal"
                    class="w-full rounded-lg bg-gray-800 px-4 py-3 text-white font-semibold hover:bg-black">
                    勝負レースにしない
                </button>
            </form>
        </div>

        <div>
            <a href="{{ route('bet.races') }}" class="text-sm text-blue-600 hover:underline">← レース一覧へ戻る</a>
        </div>
    </div>
</x-app-layout>
