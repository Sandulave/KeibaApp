<x-app-layout title="購入予定一覧">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">

        @if (session('success'))
            <div class="rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded bg-red-100 p-3 text-red-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded bg-red-100 p-3 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <a href="{{ route('bet.types', [$race]) }}" class="text-sm text-blue-600 underline">
                ← 買い方選択に戻る
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
            <div class="text-sm text-gray-600">レース</div>
            <div class="font-semibold">{{ $race->name }}</div>
        </div>

        @php
            $items = $cart['items'] ?? [];
            $totalCount = count($items);
            $totalAmount = collect($items)->sum(fn($i) => (int) ($i['amount'] ?? 0));
            $betLabels = config('domain.bet.type_labels', []);
            $betOrder = [
                'tansho' => 1,
                'fukusho' => 2,
                'umaren' => 3,
                'wide' => 4,
                'umatan' => 5,
                'sanrenpuku' => 6,
                'sanrentan' => 7,
                'wakuren' => 8,
            ];
            $displayItems = collect($items)
                ->values()
                ->map(function ($item, $index) {
                    $item['original_index'] = $index;
                    return $item;
                })
                ->sort(function ($a, $b) use ($betOrder) {
                    $aTypeOrder = $betOrder[$a['bet_type'] ?? ''] ?? 999;
                    $bTypeOrder = $betOrder[$b['bet_type'] ?? ''] ?? 999;
                    if ($aTypeOrder !== $bTypeOrder) {
                        return $aTypeOrder <=> $bTypeOrder;
                    }

                    $aKey = (string) ($a['selection_key'] ?? '');
                    $bKey = (string) ($b['selection_key'] ?? '');
                    $cmp = strnatcmp($aKey, $bKey);
                    if ($cmp !== 0) {
                        return $cmp;
                    }

                    return ((int) ($a['original_index'] ?? 0)) <=> ((int) ($b['original_index'] ?? 0));
                })
                ->values()
                ->all();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
                <div class="text-sm text-gray-600">点数</div>
                <div class="text-2xl font-semibold">{{ number_format($totalCount) }} 点</div>
            </div>
            <div id="totalAmountDisplay" class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
                <div class="text-sm text-gray-600">合計金額</div>
                <div class="text-2xl font-semibold">{{ number_format($totalAmount) }} 円</div>
            </div>
        </div>

        {{-- 金額更新（0円にした行は削除される仕様） --}}
        <form id="cart-update-form" method="POST" action="{{ route('bet.cart.update', $race) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $commitToken ?? '' }}">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-2">
                <div class="hidden md:grid grid-cols-12 gap-2 text-xs text-gray-500">
                    <div class="col-span-2">券種</div>
                    <div class="col-span-6">買い目</div>
                    <div class="col-span-2">金額</div>
                    <div class="col-span-2 flex items-center justify-end gap-2">
                        <button type="button" id="select-all-btn"
                            class="rounded border border-blue-300 px-2 py-1 text-blue-700 hover:bg-blue-50">
                            全選択
                        </button>
                        <button type="button" id="clear-select-btn"
                            class="rounded border border-gray-300 px-2 py-1 text-gray-700 hover:bg-gray-50">
                            クリア
                        </button>
                    </div>
                </div>

                @forelse($displayItems as $item)
                    @php
                        $i = (int) ($item['original_index'] ?? 0);
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-2 items-start md:items-center border border-gray-100 rounded-lg p-3 md:border-0 md:rounded-none md:p-0">
                        <div class="md:col-span-2 text-sm font-medium">
                            {{ $betLabels[$item['bet_type']] ?? $item['bet_type'] }}
                        </div>

                        <div class="md:col-span-6 text-sm font-mono break-all">
                            {{ $item['selection_key'] }}
                        </div>

                        <div class="md:col-span-2">
                            <input type="number" min="0" step="100"
                                name="items[{{ $i }}][amount]" value="{{ (int) $item['amount'] }}"
                                class="w-full rounded border-gray-300 amount-input" />
                            <div class="text-[11px] text-gray-500 mt-1">
                                0にすると削除
                            </div>
                        </div>

                        <div class="md:col-span-2 grid grid-cols-2 items-center gap-2">
                            <button type="submit"
                                name="action"
                                value="update_amount"
                                class="justify-self-start text-xs rounded bg-blue-600 text-white px-2 py-1 hover:bg-blue-700">
                                金額更新
                            </button>
                            <input type="checkbox" name="selected_indexes[]" value="{{ $i }}"
                                class="js-row-select justify-self-center h-5 w-5 rounded border-2 border-gray-400 text-blue-600 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">まだ買い目がありません</div>
                @endforelse
            </div>
        </form>

        {{-- 画面下 固定フッター（操作バー） --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-md z-50">
            <div class="max-w-5xl mx-auto p-4 space-y-3">

                {{-- 上段：全削除 / 追加 --}}
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('bet.types', $race) }}"
                        class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                        買い目を追加する
                    </a>

                    <button type="submit"
                        form="cart-update-form"
                        formaction="{{ route('bet.cart.update', $race) }}"
                        formmethod="POST"
                        name="action"
                        value="selected_remove"
                        class="rounded bg-white px-4 py-2 text-gray-900 ring-1 ring-gray-200 hover:bg-gray-50 disabled:opacity-50"
                        @disabled($totalCount === 0)
                        onclick="return confirm('選択した買い目を削除しますか？')">
                        選択した項目を削除
                    </button>

                    <button type="submit"
                        form="cart-update-form"
                        formaction="{{ route('bet.cart.update', $race) }}"
                        formmethod="POST"
                        name="action"
                        value="unselected_remove"
                        class="rounded bg-white px-4 py-2 text-gray-900 ring-1 ring-gray-200 hover:bg-gray-50 disabled:opacity-50"
                        @disabled($totalCount === 0)
                        onclick="return confirm('選択していない買い目を削除しますか？')">
                        選択以外を削除
                    </button>

                    {{-- 全削除は update フォームに投げる --}}
                    <form method="POST" action="{{ route('bet.cart.update', $race) }}" class="ml-2">
                        @csrf
                        <button type="submit" name="action" value="clear"
                            class="rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                            onclick="return confirm('カートを空にしますか？')">
                            全削除
                        </button>
                    </form>
                </div>

                {{-- 下段：確定（DB登録） --}}
                <button type="submit"
                    form="cart-update-form"
                    formaction="{{ route('bet.commit', $race) }}"
                    formmethod="POST"
                    class="w-full rounded bg-blue-600 px-4 py-3 text-white font-semibold disabled:opacity-50"
                    @disabled($totalCount === 0) onclick="return confirm('この内容で購入を確定しますか？')">
                    決定（DB登録）
                </button>

            </div>
        </div>

        {{-- 固定バー分の余白（これ必須） --}}
        <div class="h-40"></div>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const inputs = document.querySelectorAll('.amount-input');
            const totalDisplay = document.getElementById('totalAmountDisplay');
            const selectAllBtn = document.getElementById('select-all-btn');
            const clearSelectBtn = document.getElementById('clear-select-btn');
            const rowSelects = document.querySelectorAll('.js-row-select');

            function updateTotal() {
                let total = 0;

                inputs.forEach(input => {
                    const value = parseInt(input.value) || 0;
                    total += value;
                });

                totalDisplay.textContent = total.toLocaleString() + ' 円';
            }

            inputs.forEach(input => {
                input.addEventListener('input', updateTotal);
            });

            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', () => {
                    rowSelects.forEach((checkbox) => {
                        checkbox.checked = true;
                    });
                });
            }

            if (clearSelectBtn) {
                clearSelectBtn.addEventListener('click', () => {
                    rowSelects.forEach((checkbox) => {
                        checkbox.checked = false;
                    });
                });
            }

        });
    </script>

</x-app-layout>
