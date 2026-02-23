<x-app-layout title="購入馬券作成（{{ $race->name }}）">
    @php
        $oldAxis = (string) old('axis', '');
        $oldOpp  = collect(old('opponents', []))->map(fn($v) => (string) $v)->all();
        $oldAmount = old('amount', 100);

        $horseNos = $horseNos ?? range(1, (int) ($race->horse_count ?? 18));

        // ワイド 1頭軸流し：相手数（軸がある時だけ）
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
        #wideNagashi1Axis .combo-scroll { scrollbar-width: thin; }
        #wideNagashi1Axis .combo-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        #wideNagashi1Axis .combo-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        #wideNagashi1Axis .combo-scroll::-webkit-scrollbar-track { background: transparent; }
    </style>

    <div id="wideNagashi1Axis" class="space-y-4">
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
            <input type="hidden" name="mode" value="{{ $mode ?? 'nagashi_1axis' }}">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">ワイド 1頭軸流し</h2>
                </div>

                <div class="text-sm text-gray-600 leading-relaxed">
                    軸馬を1頭、相手を1頭以上選びます。<br>
                    ワイドは順不同なので、表示は <strong>昇順（1-2）</strong> に固定します。
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="lg:col-span-2">
                        <div class="flex items-end justify-between mb-2 gap-2">
                            <div>
                                <div class="text-sm font-medium">選択</div>
                                <div class="text-xs text-gray-500">軸1頭／相手1頭以上（軸と同じ馬は相手にできません）</div>
                            </div>

                            <div class="flex gap-2 flex-wrap justify-end">
                                <button type="button" id="axisClear" class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">軸 クリア</button>
                                <button type="button" id="oppAll" class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">相手 全通り</button>
                                <button type="button" id="oppClear" class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">相手 クリア</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-16">枠</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-20">馬番</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-40">軸</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-44">相手</th>
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
                                            <td class="px-3 py-2 font-semibold text-gray-900">{{ $noStr }}</td>
                                            <td class="px-3 py-2 cursor-pointer" data-col="axis" data-no="{{ $noStr }}">
                                                <label class="inline-flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded hover:bg-gray-100 focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="radio" name="axis" value="{{ $noStr }}" class="h-4 w-4 border-gray-300 text-indigo-600" aria-label="軸：馬番{{ $noStr }}" @checked($oldAxis === $noStr)>
                                                    <span class="text-gray-700">軸</span>
                                                </label>
                                            </td>
                                            <td class="px-3 py-2 cursor-pointer" data-col="opp" data-no="{{ $noStr }}">
                                                <label class="inline-flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded hover:bg-gray-100 focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="opponents[]" value="{{ $noStr }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600" aria-label="相手：馬番{{ $noStr }}" @checked(in_array($noStr, $oldOpp, true))>
                                                    <span class="text-gray-700">相手</span>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 space-y-1">
                            @error('axis') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                            @error('opponents') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                            @error('opponents.*') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">

                            <div class="rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden">
                                <div class="bg-indigo-600 text-white px-4 py-3"><div class="text-sm font-semibold">ワイド 1頭軸流し</div></div>
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

                                    <div class="space-y-2">
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">軸</div>
                                            <div id="axisInline" class="text-sm text-gray-800">未選択</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">相手</div>
                                            <div id="oppInline" class="text-sm text-gray-800">未選択</div>
                                        </div>
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

            const axisInlineEl = document.getElementById('axisInline');
            const oppInlineEl = document.getElementById('oppInline');

            const comboWrap = document.getElementById('comboWrap');
            const comboShownEl = document.getElementById('comboShown');
            const comboTotalEl = document.getElementById('comboTotal');
            const comboNoteEl = document.getElementById('comboNote');

            const submitBtn = document.getElementById('submitBtn');
            const axisClearBtn = document.getElementById('axisClear');
            const oppAllBtn = document.getElementById('oppAll');
            const oppClearBtn = document.getElementById('oppClear');

            const axisSelector = 'input[name="axis"]';
            const oppSelector = 'input[name="opponents[]"]';

            function normalize(values) {
                const uniq = Array.from(new Set(values.map(v => String(v).trim()).filter(v => v !== '')));
                uniq.sort((a, b) => (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0));
                return uniq;
            }

            function selectedAxis() {
                const el = horseGrid.querySelector(`${axisSelector}:checked`);
                return el ? String(el.value) : '';
            }

            function selectedOpponents() {
                return Array.from(horseGrid.querySelectorAll(`${oppSelector}:checked`)).map(i => String(i.value));
            }

            function syncAxisAndOpp(axis) {
                horseGrid.querySelectorAll(oppSelector).forEach(cb => {
                    const v = String(cb.value);
                    if (axis && v === axis) {
                        cb.checked = false;
                        cb.disabled = true;
                    } else {
                        cb.disabled = false;
                    }
                });
            }

            function buildPairs(axis, opp) {
                if (!axis) return [];
                const pairs = opp.map(o => {
                    const a = axis;
                    const b = o;
                    if (a === b) return null;
                    const x = (parseInt(a, 10) || 0);
                    const y = (parseInt(b, 10) || 0);
                    return x <= y ? `${a}-${b}` : `${b}-${a}`;
                }).filter(Boolean);

                return Array.from(new Set(pairs)).sort((u, v) => {
                    const [a1, a2] = u.split('-').map(n => parseInt(n, 10) || 0);
                    const [b1, b2] = v.split('-').map(n => parseInt(n, 10) || 0);
                    if (a1 !== b1) return a1 - b1;
                    return a2 - b2;
                });
            }

            function renderPairs(keys) {
                comboWrap.innerHTML = '';
                comboTotalEl.textContent = keys.length.toLocaleString('ja-JP');

                if (keys.length === 0) {
                    comboShownEl.textContent = '0';
                    comboNoteEl.classList.add('hidden');
                    comboWrap.innerHTML = `<div class="text-sm text-gray-400 text-center py-6">軸1頭＋相手1頭以上で表示されます</div>`;
                    return;
                }

                const shown = keys.slice(0, MAX_COMBO_PREVIEW);
                comboShownEl.textContent = shown.length.toLocaleString('ja-JP');

                if (keys.length > MAX_COMBO_PREVIEW) {
                    comboNoteEl.classList.remove('hidden');
                    comboNoteEl.textContent = `※表示は先頭 ${MAX_COMBO_PREVIEW.toLocaleString('ja-JP')} 点までです（全 ${keys.length.toLocaleString('ja-JP')} 点）`;
                } else {
                    comboNoteEl.classList.add('hidden');
                }

                shown.forEach(k => {
                    const [a, b] = k.split('-');
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-1 bg-gray-50 rounded-lg px-2 py-1 text-sm';
                    div.innerHTML = `
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${a}</span>
                        <span class="text-gray-400">-</span>
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${b}</span>
                    `;
                    comboWrap.appendChild(div);
                });
            }

            function updateRowHighlight(axis, oppSet) {
                horseGrid.querySelectorAll('tr[data-row]').forEach(tr => {
                    const no = tr.getAttribute('data-row');
                    const on = (no === axis) || oppSet.has(no);
                    tr.classList.toggle('bg-gray-50', on);
                });
            }

            function updateAll() {
                const axis = selectedAxis();
                const oppRaw = normalize(selectedOpponents());
                syncAxisAndOpp(axis);

                const opp = oppRaw.filter(v => v !== axis);

                axisInlineEl.textContent = axis ? axis : '未選択';
                oppInlineEl.textContent = opp.length ? opp.join(' / ') : '未選択';

                let amount = parseInt(amountEl.value || '0', 10);
                if (Number.isNaN(amount)) amount = 0;
                if (amount < 100) amount = 100;
                amount = Math.floor(amount / 100) * 100;

                const t = axis ? opp.length : 0;
                ticketsEl.textContent = t.toLocaleString('ja-JP');
                totalEl.textContent = (t * amount).toLocaleString('ja-JP');

                updateRowHighlight(axis, new Set(opp));

                const keys = buildPairs(axis, opp);
                renderPairs(keys);

                submitBtn.classList.toggle('opacity-50', t === 0);
            }

            horseGrid.addEventListener('click', (e) => {
                if (e.target.closest('input[type="checkbox"], input[type="radio"], label, button')) return;
                const td = e.target.closest('td[data-col][data-no]');
                if (!td) return;
                const col = td.getAttribute('data-col');
                const no = td.getAttribute('data-no');
                if (!col || !no) return;

                if (col === 'axis') {
                    const r = td.querySelector(`${axisSelector}[value="${no}"]`);
                    if (!r) return;
                    r.checked = true;
                    updateAll();
                    return;
                }

                if (col === 'opp') {
                    const cb = td.querySelector(`${oppSelector}[value="${no}"]`);
                    if (!cb || cb.disabled) return;
                    cb.checked = !cb.checked;
                    updateAll();
                    return;
                }
            });

            horseGrid.addEventListener('change', (e) => {
                if (e.target.matches(axisSelector) || e.target.matches(oppSelector)) updateAll();
            });

            axisClearBtn.addEventListener('click', () => {
                const checked = horseGrid.querySelector(`${axisSelector}:checked`);
                if (checked) checked.checked = false;
                updateAll();
            });

            oppAllBtn.addEventListener('click', () => {
                horseGrid.querySelectorAll(oppSelector).forEach(cb => {
                    if (!cb.disabled) cb.checked = true;
                });
                updateAll();
            });

            oppClearBtn.addEventListener('click', () => {
                horseGrid.querySelectorAll(oppSelector).forEach(cb => cb.checked = false);
                updateAll();
            });

            amountEl.addEventListener('input', updateAll);

            updateAll();
        })();
    </script>
</x-app-layout>
