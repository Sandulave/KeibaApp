<x-app-layout title="購入馬券作成（{{ $race->name }}）">
    @php
        $oldHorses = collect(old('horses', []))->map(fn($v) => (string) $v)->all();
        $oldAmount = old('amount', 100);

        $horseNos = $horseNos ?? range(1, (int) ($race->horse_count ?? 18));

        // ワイド ボックス：C(n,2)
        $MAX_COMBO_PREVIEW = 240;

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
            for ($f = 1; $f <= 8; $f++) $frameCounts[$f] = 0;
            for ($i = 0; $i < $n; $i++) $frameCounts[$i + 1] = 1;
        } elseif ($n <= 15) {
            for ($f = 1; $f <= 8; $f++) $frameCounts[$f] = 1;
            $extra = $n - 8;
            for ($f = 8; $f >= 1 && $extra > 0; $f--) { $frameCounts[$f] += 1; $extra--; }
        } else {
            for ($f = 1; $f <= 8; $f++) $frameCounts[$f] = 2;
            $extra = $n - 16;
            for ($f = 8; $f >= 1 && $extra > 0; $f--) { $frameCounts[$f] += 1; $extra--; }
        }

        $horseToFrame = [];
        $idx = 0;
        for ($f = 1; $f <= 8; $f++) {
            for ($c = 0; $c < $frameCounts[$f]; $c++) {
                if (!isset($horsesSorted[$idx])) break;
                $horseToFrame[(int) $horsesSorted[$idx]] = $f;
                $idx++;
            }
        }
    @endphp

    <style>
        #wideBox .combo-scroll { scrollbar-width: thin; }
        #wideBox .combo-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        #wideBox .combo-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        #wideBox .combo-scroll::-webkit-scrollbar-track { background: transparent; }
    </style>

    <div id="wideBox" class="space-y-4">

        <div class="flex items-center justify-between">
            <a href="{{ route('bet.modes', [$race, $betType]) }}" class="text-sm text-blue-600 underline">← 買い方選択に戻る</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
            <div class="text-sm text-gray-600">レース</div>
            <div class="font-semibold">{{ $race->name }}</div>
        </div>

        @if ($errors->any())
            <div class="rounded bg-red-100 p-3 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bet.cart.add', $race) }}" class="space-y-4">
            @csrf

            <input type="hidden" name="betType" value="{{ $betType ?? 'wide' }}">
            <input type="hidden" name="mode" value="{{ $mode ?? 'box' }}">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">ワイド ボックス</h2>
                </div>

                <div class="text-sm text-gray-600 leading-relaxed">
                    馬番を2頭以上選ぶと、組み合わせ（C(n,2)）で自動生成します。<br>
                    ワイドは順不同なので、表示は <strong>昇順（1-2）</strong> に固定します。
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <div class="flex items-end justify-between mb-2 gap-2">
                            <div>
                                <div class="text-sm font-medium">選択</div>
                                <div class="text-xs text-gray-500">2頭以上</div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="all" class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">全通り</button>
                                <button type="button" id="clear" class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">クリア</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-16">枠</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-14">馬番</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-40">馬名</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-44">選択</th>
                                    </tr>
                                </thead>
                                <tbody id="horseGrid" class="divide-y divide-gray-100 bg-white">
                                    @foreach ($horsesSorted as $no)
                                        @php
                                            $frame = $horseToFrame[(int) $no] ?? 1;
                                            $frameClass = $frameColors[$frame] ?? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200';
                                            $noStr = (string) $no;
                                        @endphp
                                        <tr data-row="{{ $noStr }}" class="hover:bg-gray-50">
                                            <td class="px-3 py-2"><span class="inline-flex w-10 h-8 items-center justify-center rounded-md text-xs font-bold {{ $frameClass }}">{{ $frame }}</span></td>
                                            <td class="px-2 py-2 font-semibold text-gray-900">
                                                {{ $noStr }}
                                            </td>
                                            <td class="px-3 py-2 !text-left text-base font-medium text-gray-800">
                                                {{ $horseNameByNo[$noStr] ?? '-' }}
                                            </td>
                                            <td class="px-3 py-2 cursor-pointer" data-col="horse" data-no="{{ $noStr }}">
                                                <label class="inline-flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded hover:bg-gray-100 focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="horses[]" value="{{ $noStr }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600" aria-label="馬番{{ $noStr }}" @checked(in_array($noStr, $oldHorses, true))>
                                                    <span class="text-gray-700">選択</span>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 space-y-1">
                            @error('horses') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                            @error('horses.*') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">
                            <div class="rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden">
                                <div class="bg-indigo-600 text-white px-4 py-3"><div class="text-sm font-semibold">ワイド ボックス</div></div>
                                <div class="p-4 space-y-3">
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <div class="text-xs text-gray-500">点数</div>
                                            <div class="text-4xl font-bold text-gray-900"><span id="tickets">0</span><span class="text-lg ml-1">点</span></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs text-gray-500">合計</div>
                                            <div class="text-lg font-semibold text-gray-900"><span id="total">0</span> 円</div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-xs text-gray-500 mb-1">選択馬</div>
                                        <div id="selectedInline" class="text-sm text-gray-800">未選択</div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium">1点あたり金額</label>
                                        <input id="amount" type="number" min="100" step="100" name="amount" value="{{ $oldAmount }}" class="mt-1 w-full rounded border-gray-300">
                                        @error('amount') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <button id="submitBtn" type="submit" class="w-full rounded-xl bg-gradient-to-r from-red-500 to-red-600 px-4 py-3 text-white font-semibold shadow-md hover:opacity-90 transition">まとめてカートに追加</button>
                                </div>
                            </div>

                            <div class="rounded-xl ring-1 ring-gray-200 bg-white p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold">組み合わせプレビュー</div>
                                    <div class="text-xs text-gray-500">表示 <span id="comboShown" class="font-semibold">0</span> / 全 <span id="comboTotal" class="font-semibold">0</span></div>
                                </div>
                                <div id="comboNote" class="text-xs text-gray-500 hidden"></div>
                                <div id="comboWrap" class="combo-scroll h-[calc(100vh-360px)] min-h-72 overflow-auto space-y-2 rounded-lg border border-gray-200 p-2">
                                    <div class="text-sm text-gray-400 text-center py-6">選択するとここに表示されます</div>
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
            const MAX_COMBO_PREVIEW = {{ (int) $MAX_COMBO_PREVIEW }};

            const horseGrid = document.getElementById('horseGrid');
            const amountEl = document.getElementById('amount');
            const ticketsEl = document.getElementById('tickets');
            const totalEl = document.getElementById('total');
            const selectedInlineEl = document.getElementById('selectedInline');

            const comboWrap = document.getElementById('comboWrap');
            const comboShownEl = document.getElementById('comboShown');
            const comboTotalEl = document.getElementById('comboTotal');
            const comboNoteEl = document.getElementById('comboNote');

            const submitBtn = document.getElementById('submitBtn');
            const allBtn = document.getElementById('all');
            const clearBtn = document.getElementById('clear');

            const horseSelector = 'input[name="horses[]"]';

            function normalize(values) {
                const uniq = Array.from(new Set(values.map(v => String(v).trim()).filter(v => v !== '')));
                uniq.sort((a, b) => (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0));
                return uniq;
            }

            function selectedHorses() {
                return Array.from(horseGrid.querySelectorAll(`${horseSelector}:checked`)).map(i => String(i.value));
            }

            function comb2(n) {
                if (n < 2) return 0;
                return (n * (n - 1)) / 2;
            }

            function buildPairs(horses, limit = MAX_COMBO_PREVIEW) {
                const pairs = [];
                const n = horses.length;
                const total = comb2(n);
                if (n < 2) return { pairs, total: 0 };

                for (let i = 0; i < n; i++) {
                    for (let j = i + 1; j < n; j++) {
                        if (pairs.length < limit) pairs.push([horses[i], horses[j]]);
                    }
                }

                return { pairs, total };
            }

            function updateRowHighlight(set) {
                horseGrid.querySelectorAll('tr[data-row]').forEach(tr => {
                    const no = tr.getAttribute('data-row');
                    tr.classList.toggle('bg-gray-50', set.has(no));
                });
            }

            function renderPairs(horses) {
                const { pairs, total } = buildPairs(horses, MAX_COMBO_PREVIEW);

                comboTotalEl.textContent = total.toLocaleString('ja-JP');
                comboWrap.innerHTML = '';

                if (horses.length < 2) {
                    comboShownEl.textContent = '0';
                    comboNoteEl.classList.add('hidden');
                    comboWrap.innerHTML = `<div class="text-sm text-gray-400 text-center py-6">2頭以上選ぶと表示されます</div>`;
                    return;
                }

                comboShownEl.textContent = pairs.length.toLocaleString('ja-JP');

                if (total > MAX_COMBO_PREVIEW) {
                    comboNoteEl.classList.remove('hidden');
                    comboNoteEl.textContent = `※表示は先頭 ${MAX_COMBO_PREVIEW.toLocaleString('ja-JP')} 点までです（全 ${total.toLocaleString('ja-JP')} 点）`;
                } else {
                    comboNoteEl.classList.add('hidden');
                }

                pairs.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-1 bg-gray-50 rounded-lg px-2 py-1 text-sm';
                    div.innerHTML = `
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${p[0]}</span>
                        <span class="text-gray-400">-</span>
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${p[1]}</span>
                    `;
                    comboWrap.appendChild(div);
                });
            }

            function updateAll() {
                const horses = normalize(selectedHorses());
                selectedInlineEl.textContent = horses.length ? horses.join(' / ') : '未選択';

                let amount = parseInt(amountEl.value || '0', 10);
                if (Number.isNaN(amount)) amount = 0;
                if (amount < 100) amount = 100;
                amount = Math.floor(amount / 100) * 100;

                const t = comb2(horses.length);
                ticketsEl.textContent = t.toLocaleString('ja-JP');
                totalEl.textContent = (t * amount).toLocaleString('ja-JP');
                updateRowHighlight(new Set(horses));
                renderPairs(horses);

                submitBtn.classList.toggle('opacity-50', t === 0);
            }

            horseGrid.addEventListener('click', (e) => {
                if (e.target.closest('input[type="checkbox"], label, button')) return;
                const td = e.target.closest('td[data-col][data-no]');
                if (!td) return;
                const no = td.getAttribute('data-no');
                const cb = td.querySelector(`${horseSelector}[value="${no}"]`);
                if (!cb) return;
                cb.checked = !cb.checked;
                updateAll();
            });

            horseGrid.addEventListener('change', (e) => {
                if (e.target.matches(horseSelector)) updateAll();
            });

            allBtn.addEventListener('click', () => {
                horseGrid.querySelectorAll(horseSelector).forEach(cb => cb.checked = true);
                updateAll();
            });

            clearBtn.addEventListener('click', () => {
                horseGrid.querySelectorAll(horseSelector).forEach(cb => cb.checked = false);
                updateAll();
            });

            amountEl.addEventListener('input', updateAll);

            updateAll();
        })();
    </script>
</x-app-layout>
