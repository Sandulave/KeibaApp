<x-app-layout :title="$race->name . ' - 結果登録'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <div>
            <a href="{{ route('races.show', $race) }}" class="text-sm text-blue-600 hover:underline">
                ← 詳細に戻る
            </a>
            <h1 class="mt-2 text-2xl font-bold">{{ $race->name }}：結果登録</h1>
            <p class="text-sm text-gray-500 mt-1">馬番で 1〜3着を登録します※同着にも対応しています</p>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 ring-1 ring-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6">
            <form method="POST" action="{{ route('races.result.update', $race) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @for ($rank = 1; $rank <= 3; $rank++)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $rank }}着（複数選択可）</label>
                            <div class="flex flex-wrap gap-2">
                                @for ($i = 1; $i <= 18; $i++)
                                    <label class="inline-flex items-center cursor-pointer" style="font-size:1rem;">
                                        <input type="checkbox" name="ranks[{{ $rank }}][]" value="{{ $i }}"
                                            style="width:24px;height:24px;min-width:24px;min-height:24px;"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            @if(is_array(old('ranks.' . $rank)))
                                                {{ in_array($i, old('ranks.' . $rank, [])) ? 'checked' : '' }}
                                            @endif
                                        >
                                        <span class="ml-6" style="font-size:1rem;">{{ $i }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>
                    @endfor
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                        保存
                    </button>
                    <a href="{{ route('races.show', $race) }}" class="text-sm text-gray-600 hover:underline">
                        キャンセル
                    </a>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-3">現在の登録内容</h2>

            @php
                $results = $race->results ?? collect();
                $byRank = $results->groupBy('rank');
            @endphp
            @if($byRank->isNotEmpty())
                <ul class="space-y-2">
                    @foreach([1,2,3] as $rank)
                        <li>
                            <span class="font-semibold">{{ $rank }}着:</span>
                            @if($byRank->has($rank))
                                {{ $byRank[$rank]->pluck('horse_no')->sort()->implode(', ') }}
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-500">未登録です。</p>
            @endif
        </section>

    </div>
</x-app-layout>
