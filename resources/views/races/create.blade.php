<x-app-layout title="レース登録">
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
                レース登録
            </h1>
            <p class="mt-2 text-sm text-gray-500">
                新しいレース情報を登録してください。
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
        <form method="POST" action="{{ route('races.store') }}" class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6">
            @csrf

            {{-- 名前 --}}
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-900">
                    レース名 <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="例: 有馬記念">
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
                    value="{{ old('race_date') }}"
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
                    value="{{ old('horse_count', 0) }}"
                    min="0"
                    max="18"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                @error('horse_count')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-medium text-gray-900">馬名（任意）</p>
                <p class="mt-1 text-xs text-gray-500">先に頭数を入力してください。</p>
                <div class="mt-3 grid grid-cols-1 gap-2">
                    @for ($horseNo = 1; $horseNo <= 18; $horseNo++)
                        <label class="flex items-center gap-2 text-sm" data-horse-name-row="{{ $horseNo }}">
                            <span class="w-10 shrink-0 text-gray-600">{{ $horseNo }}番</span>
                            <input
                                type="text"
                                name="horse_names[{{ $horseNo }}]"
                                value="{{ old("horse_names.{$horseNo}", $horseNameByNo[$horseNo] ?? '') }}"
                                class="w-full rounded border-gray-300 text-sm"
                                placeholder="馬名">
                        </label>
                    @endfor
                </div>
                @error('horse_names')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('horse_names.*')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
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
                    value="{{ old('course') }}"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="例: 東京">
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
                    value="{{ old('result') }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="">
                @error('result')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- ボタン --}}
            <div class="flex gap-3">
                <button
                    type="submit"
                    class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700 transition-colors">
                    登録
                </button>
                <a
                    href="{{ route('races.index') }}"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-gray-900 font-medium hover:bg-gray-50 transition-colors text-center">
                    キャンセル
                </a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const horseCountInput = document.getElementById('horse_count');
            const horseNameRows = Array.from(document.querySelectorAll('[data-horse-name-row]'));

            if (!horseCountInput || horseNameRows.length === 0) {
                return;
            }

            const updateHorseNameRows = () => {
                const rawCount = Number.parseInt(horseCountInput.value, 10);
                const count = Number.isNaN(rawCount) ? 0 : Math.max(0, Math.min(18, rawCount));

                horseNameRows.forEach((row) => {
                    const rowNo = Number.parseInt(row.dataset.horseNameRow || '0', 10);
                    row.classList.toggle('hidden', rowNo > count);
                });
            };

            horseCountInput.addEventListener('input', updateHorseNameRows);
            updateHorseNameRows();
        });
    </script>
</x-app-layout>
