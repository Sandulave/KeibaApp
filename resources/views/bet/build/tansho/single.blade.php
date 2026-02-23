<x-app-layout title="購入馬券作成（{{ $race->name }}）">
    @php
        $oldHorses = collect(old('horse', []))->map(fn($v) => (string) $v)->all();
        $oldAmount = old('amount', 100);

        $horseNos = $horseNos ?? range(1, (int) ($race->horse_count ?? 18));

        $frameColors = [
            1 => 'bg-white text-gray-900 ring-1 ring-gray-300',
            2 => 'bg-gray-900 text-white',
            3 => 'bg-red-600 text-white',
            4 => 'bg-blue-600 text-white',
            5 => 'bg-yellow-400 text-gray-900',
            6 => 'bg-green-600 text-white',
            7 => 'bg-orange-500 text-white',
            8 => 'bg-pink-500 text-white',
        ];

        $horsesSorted = collect($horseNos)->map(fn($v) => (int) $v)->unique()->sort()->values()->all();
        $n = count($horsesSorted);

        $frameCounts = array_fill(1, 8, 0);

        if ($n <= 8) {
            for ($i = 0; $i < $n; $i++) {
                $frameCounts[$i + 1] = 1;
            }
        } elseif ($n <= 15) {
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 1;
            }
            $extra = $n - 8;
            for ($f = 8; $f >= 1 && $extra > 0; $f--) {
                $frameCounts[$f]++;
                $extra--;
            }
        } else {
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 2;
            }
            $extra = $n - 16;
            for ($f = 8; $f >= 1 && $extra > 0; $f--) {
                $frameCounts[$f]++;
                $extra--;
            }
        }

        $horseToFrame = [];
        $idx = 0;
        for ($f = 1; $f <= 8; $f++) {
            for ($c = 0; $c < $frameCounts[$f]; $c++) {
                if (!isset($horsesSorted[$idx])) {
                    break;
                }
                $horseToFrame[$horsesSorted[$idx]] = $f;
                $idx++;
            }
        }
    @endphp

    <div id="tanshoMulti" class="space-y-4">

        <div class="flex items-center justify-between">
            <a href="{{ route('bet.modes', [$race, $betType]) }}" class="text-sm text-blue-600 underline">
                ← 買い方選択に戻る
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
            <div class="text-sm text-gray-600">レース</div>
            <div class="font-semibold">{{ $race->name }}</div>
        </div>

        <form method="POST" action="{{ route('bet.cart.add', $race) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="betType" value="{{ $betType ?? 'tansho' }}">
            <input type="hidden" name="mode" value="{{ $mode ?? 'single' }}">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-4">

                <h2 class="text-base font-semibold">単勝（複数選択可）</h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- 左 --}}
                    <div class="lg:col-span-2">

                        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-20">枠</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-24">馬番</th>
                                        <th class="px-3 py-2 text-center font-medium text-gray-600 w-32">選択</th>
                                    </tr>
                                </thead>

                                <tbody id="horseGrid" class="divide-y divide-gray-100 bg-white">
                                    @foreach ($horsesSorted as $no)
                                        @php
                                            $frame = $horseToFrame[(int) $no] ?? 1;
                                            $frameClass =
                                                $frameColors[$frame] ??
                                                'bg-gray-100 text-gray-900 ring-1 ring-gray-200';
                                            $noStr = (string) $no;
                                        @endphp
                                        <tr data-row="{{ $noStr }}" class="hover:bg-gray-50">
                                            <td class="px-3 py-2">
                                                <span
                                                    class="inline-flex w-10 h-8 items-center justify-center rounded-md text-xs font-bold {{ $frameClass }}">
                                                    {{ $frame }}
                                                </span>
                                            </td>

                                            <td class="px-3 py-2 font-semibold text-gray-900">
                                                {{ $noStr }}
                                            </td>

                                            {{-- ✅ セル全体クリックできるようにする --}}
                                            <td class="px-3 py-2 text-center cursor-pointer"
                                                data-no="{{ $noStr }}">
                                                <label
                                                    class="inline-flex items-center justify-center w-full py-2 rounded hover:bg-gray-100 cursor-pointer
                                                          focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="horse[]" value="{{ $noStr }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                        @checked(in_array($noStr, $oldHorses, true))>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('horse')
                            <div class="text-sm text-red-600 mt-2">{{ $message }}</div>
                        @enderror

                    </div>

                    {{-- 右 --}}
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">

                            <div class="rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden">
                                <div class="bg-indigo-600 text-white px-4 py-3">
                                    <div class="text-sm font-semibold">単勝</div>
                                </div>

                                <div class="p-4 space-y-3">
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <div class="text-xs text-gray-500">点数</div>
                                            <div class="text-4xl font-bold text-gray-900">
                                                <span id="tickets">0</span>
                                                <span class="text-lg ml-1">点</span>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <div class="text-xs text-gray-500">合計</div>
                                            <div class="text-lg font-semibold text-gray-900">
                                                <span id="total">0</span> 円
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium">金額</label>
                                        <input id="amount" type="number" min="100" step="100"
                                            name="amount" value="{{ $oldAmount }}"
                                            class="mt-1 w-full rounded border-gray-300">
                                    </div>

                                    <button type="submit"
                                        class="w-full rounded-xl bg-gradient-to-r from-red-500 to-red-600
                                                   px-4 py-3 text-white font-semibold shadow-md hover:opacity-90 transition">
                                        カートに追加
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const grid = document.getElementById('horseGrid');
            const amountEl = document.getElementById('amount');
            const ticketsEl = document.getElementById('tickets');
            const totalEl = document.getElementById('total');

            function getSelected() {
                return Array.from(grid.querySelectorAll('input[name="horse[]"]:checked')).map(i => i.value);
            }

            function normalizeAmount() {
                let amount = parseInt(amountEl.value || '0', 10);
                if (Number.isNaN(amount)) amount = 0;
                if (amount < 100) amount = 100;
                amount = Math.floor(amount / 100) * 100;
                return amount;
            }

            function update() {
                const selected = getSelected();
                const amount = normalizeAmount();
                ticketsEl.textContent = selected.length.toLocaleString('ja-JP');
                totalEl.textContent = (selected.length * amount).toLocaleString('ja-JP');
            }

            // ✅ チェックボックス以外（セル周辺）クリックでもON/OFF
            grid.addEventListener('click', (e) => {
                if (e.target.closest('input[type="checkbox"], label, button, a')) return;

                const td = e.target.closest('td[data-no]');
                if (!td) return;

                const no = td.getAttribute('data-no');
                const cb = td.querySelector(`input[name="horse[]"][value="${no}"]`);
                if (!cb) return;

                cb.checked = !cb.checked;
                update();
            });

            grid.addEventListener('change', (e) => {
                if (e.target.matches('input[name="horse[]"]')) update();
            });

            amountEl.addEventListener('input', update);

            update();
        })();
    </script>
</x-app-layout>
