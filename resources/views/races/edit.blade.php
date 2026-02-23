<x-app-layout :title="$race->name . ' を編集'">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- 戻るリンク --}}
        <div class="mb-6">
            <a href="{{ route('races.index') }}"
               class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 hover:underline">
                <span aria-hidden="true">←</span>
                レース一覧に戻る
            </a>
        </div>

        {{-- ヘッダー --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight">
                {{ $race->name }}
            </h1>
            <p class="mt-2 text-sm text-gray-500">
                レース情報を編集してください。
            </p>
        </div>

        {{-- エラー表示 --}}
        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 p-4 ring-1 ring-red-200">
                <h3 class="text-sm font-medium text-red-800 mb-2">エラーが発生しました</h3>
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm text-red-700">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- フォーム --}}
        <form method="POST" action="{{ route('races.update', $race) }}" class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6">
            @csrf
            @method('PUT')

            {{-- 名前 --}}
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-900">
                    レース名 <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $race->name) }}"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 開催日 --}}
            <div class="mb-6">
                <label for="race_date" class="block text-sm font-medium text-gray-900">
                    開催日 <span class="text-red-500">*</span>
                </label>
                <input
                    type="date"
                    id="race_date"
                    name="race_date"
                    value="{{ old('race_date', $race->race_date) }}"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                @error('race_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 頭数 --}}
            <div class="mb-6">
                <label for="horse_count" class="block text-sm font-medium text-gray-900">
                    頭数 <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    id="horse_count"
                    name="horse_count"
                    value="{{ old('horse_count', $race->horse_count ?? 18) }}"
                    min="1"
                    max="18"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                @error('horse_count')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- コース --}}
            <div class="mb-6">
                <label for="course" class="block text-sm font-medium text-gray-900">
                    コース <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="course"
                    name="course"
                    value="{{ old('course', $race->course) }}"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500">
                @error('course')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 結果（オプション） --}}
            <div class="mb-8">
                <label for="result" class="block text-sm font-medium text-gray-900">
                    結果 <span class="text-gray-500 text-xs">(オプション)</span>
                </label>
                <input
                    type="text"
                    id="result"
                    name="result"
                    value="{{ old('result', $race->result) }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500">
                @error('result')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- ボタン --}}
            <div class="flex gap-3">
                <button
                    type="submit"
                    class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700 transition-colors">
                    更新
                </button>
                <a
                    href="{{ route('races.index') }}"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-gray-900 font-medium hover:bg-gray-50 transition-colors text-center">
                    キャンセル
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
