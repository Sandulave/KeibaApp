<x-app-layout title="購入馬券作成（{{ $race->name }}）">
    @php
        // old() 復元（stringで統一）
        $oldFirst = collect(old('first', []))->map(fn($v) => (string) $v)->all();
        $oldSecond = collect(old('second', []))->map(fn($v) => (string) $v)->all();
        $oldAmount = old('amount', 100);

        // Controllerから $horseNos が来る想定。未設定でも落とさない
        $horseNos = $horseNos ?? range(1, (int) ($race->horse_count ?? 18));
        $MAX_COMBO_PREVIEW = 240;

        // 枠色（三連単UI踏襲）
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

        // horseNo を昇順ユニーク（枠割り計算もこれ基準）
        $horsesSorted = collect($horseNos)->map(fn($v) => (int) $v)->unique()->sort()->values()->all();
        $n = count($horsesSorted);

        // 枠頭数配分（三連単UI踏襲）
        $frameCounts = array_fill(1, 8, 0);

        if ($n <= 8) {
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 0;
            }
            for ($i = 0; $i < $n; $i++) {
                $frameCounts[$i + 1] = 1;
            }
        } elseif ($n <= 15) {
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 1;
            }
            $extra = $n - 8;
            for ($f = 8; $f >= 1 && $extra > 0; $f--) {
                $frameCounts[$f] += 1;
                $extra--;
            }
        } else {
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 2;
            }
            $extra = $n - 16;
            for ($f = 8; $f >= 1 && $extra > 0; $f--) {
                $frameCounts[$f] += 1;
                $extra--;
            }
        }

        // horseNo => frame の対応表
        $horseToFrame = [];
        $idx = 0;
        for ($f = 1; $f <= 8; $f++) {
            for ($c = 0; $c < $frameCounts[$f]; $c++) {
                if (!isset($horsesSorted[$idx])) {
                    break;
                }
                $horseToFrame[(int) $horsesSorted[$idx]] = $f;
                $idx++;
            }
        }
    @endphp

    <style>
        #umatanFormation .combo-scroll {
            scrollbar-width: thin;
        }

        #umatanFormation .combo-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        #umatanFormation .combo-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }

        #umatanFormation .combo-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>

    <div id="umatanFormation" class="space-y-4">

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

            {{-- BuilderResolverが必要とする情報 --}}
            <input type="hidden" name="betType" value="{{ $betType ?? 'umatan' }}">
            <input type="hidden" name="mode" value="{{ $mode ?? 'formation' }}">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">馬単 フォーメーション</h2>
                </div>

                <div class="text-sm text-gray-600 leading-relaxed">
                    1着列×2着列の組み合わせを作ります（同一馬の重複は除外）。<br>
                    馬単は順序ありなので、表示は <strong>1着→2着（1&gt;2）</strong> で固定します。
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- 左：馬一覧（表） --}}
                    <div class="lg:col-span-2">
                        <div class="flex items-end justify-between mb-2 gap-2">
                            <div>
                                <div class="text-sm font-medium">選択</div>
                                <div class="text-xs text-gray-500">各列1頭以上（同一馬の 1着=2着 は除外）</div>
                            </div>

                            <div class="flex gap-2 flex-wrap justify-end">
                                <button type="button" id="firstAll"
                                    class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">
                                    1着 全通り
                                </button>
                                <button type="button" id="firstClear"
                                    class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">
                                    1着 クリア
                                </button>

                                <button type="button" id="secondAll"
                                    class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">
                                    2着 全通り
                                </button>
                                <button type="button" id="secondClear"
                                    class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">
                                    2着 クリア
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-16">枠</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-20">馬番</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-40">1着</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-40">2着</th>
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

                                            {{-- 1着 --}}
                                            <td class="px-3 py-2 cursor-pointer" data-col="first"
                                                data-no="{{ $noStr }}">
                                                <label
                                                    class="inline-flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded hover:bg-gray-100
                                                              focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="first[]" value="{{ $noStr }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                        aria-label="1着：馬番{{ $noStr }}"
                                                        @checked(in_array($noStr, $oldFirst, true))>
                                                    <span class="text-gray-700">1着</span>
                                                </label>
                                            </td>

                                            {{-- 2着 --}}
                                            <td class="px-3 py-2 cursor-pointer" data-col="second"
                                                data-no="{{ $noStr }}">
                                                <label
                                                    class="inline-flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded hover:bg-gray-100
                                                              focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="second[]" value="{{ $noStr }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                        aria-label="2着：馬番{{ $noStr }}"
                                                        @checked(in_array($noStr, $oldSecond, true))>
                                                    <span class="text-gray-700">2着</span>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 space-y-1">
                            @error('first')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            @error('first.*')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            @error('second')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            @error('second.*')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- 右：まとめ --}}
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">

                            <div class="rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden">
                                <div class="bg-indigo-600 text-white px-4 py-3">
                                    <div class="text-sm font-semibold">馬単 フォーメーション</div>
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

                                    <div class="space-y-2">
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">1着</div>
                                            <div id="firstInline" class="text-sm text-gray-800">未選択</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">2着</div>
                                            <div id="secondInline" class="text-sm text-gray-800">未選択</div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium">1点あたり金額</label>
                                        <input id="amount" type="number" min="100" step="100"
                                            name="amount" value="{{ $oldAmount }}"
                                            class="mt-1 w-full rounded border-gray-300">
                                        @error('amount')
                                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <button id="submitBtn" type="submit"
                                        class="w-full rounded-xl bg-gradient-to-r from-red-500 to-red-600
                                               px-4 py-3 text-white font-semibold shadow-md
                                               hover:opacity-90 transition">
                                        まとめてカートに追加
                                    </button>
                                </div>
                            </div>

                            {{-- 組み合わせプレビュー --}}
                            <div class="rounded-xl ring-1 ring-gray-200 bg-white p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold">組み合わせプレビュー</div>

                                    <div class="text-xs text-gray-500">
                                        表示 <span id="comboShown" class="font-semibold">0</span> /
                                        全 <span id="comboTotal" class="font-semibold">0</span>
                                    </div>
                                </div>

                                <div id="comboNote" class="text-xs text-gray-500 hidden"></div>

                                <div id="comboWrap"
                                    class="combo-scroll h-[calc(100vh-360px)] min-h-72 overflow-auto
                                           space-y-2 rounded-lg border border-gray-200 p-2">
                                    <div class="text-sm text-gray-400 text-center py-6">
                                        選択するとここに表示されます
                                    </div>
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

            const firstInlineEl = document.getElementById('firstInline');
            const secondInlineEl = document.getElementById('secondInline');

            const comboWrap = document.getElementById('comboWrap');
            const comboShownEl = document.getElementById('comboShown');
            const comboTotalEl = document.getElementById('comboTotal');
            const comboNoteEl = document.getElementById('comboNote');

            const submitBtn = document.getElementById('submitBtn');

            const firstAllBtn = document.getElementById('firstAll');
            const firstClearBtn = document.getElementById('firstClear');
            const secondAllBtn = document.getElementById('secondAll');
            const secondClearBtn = document.getElementById('secondClear');

            const firstSelector = 'input[name="first[]"]';
            const secondSelector = 'input[name="second[]"]';

            function normalize(values) {
                const uniq = Array.from(new Set(values.map(v => String(v).trim()).filter(v => v !== '')));
                uniq.sort((a, b) => (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0));
                return uniq;
            }

            function selected(sel) {
                return Array.from(horseGrid.querySelectorAll(`${sel}:checked`)).map(i => String(i.value));
            }

            // 1着×2着 -> ordered pair（a>b）を重複排除
            function buildKeys(A, B) {
                const set = new Set();

                for (const a of A) {
                    for (const b of B) {
                        if (a === b) continue;
                        set.add(`${a}>${b}`);
                    }
                }

                return Array.from(set).sort((k1, k2) => {
                    const [a1, b1] = k1.split('>').map(v => parseInt(v, 10) || 0);
                    const [a2, b2] = k2.split('>').map(v => parseInt(v, 10) || 0);
                    if (a1 !== a2) return a1 - a2;
                    return b1 - b2;
                });
            }

            function renderCombos(keys) {
                comboWrap.innerHTML = '';
                comboTotalEl.textContent = keys.length.toLocaleString('ja-JP');

                if (keys.length === 0) {
                    comboShownEl.textContent = '0';
                    comboNoteEl.classList.add('hidden');
                    comboWrap.innerHTML = `
                        <div class="text-sm text-gray-400 text-center py-6">
                            各列を1頭以上選ぶと表示されます
                        </div>
                    `;
                    return;
                }

                const shown = keys.slice(0, MAX_COMBO_PREVIEW);
                comboShownEl.textContent = shown.length.toLocaleString('ja-JP');

                if (keys.length > MAX_COMBO_PREVIEW) {
                    comboNoteEl.classList.remove('hidden');
                    comboNoteEl.textContent =
                        `※表示は先頭 ${MAX_COMBO_PREVIEW.toLocaleString('ja-JP')} 点までです（全 ${keys.length.toLocaleString('ja-JP')} 点）`;
                } else {
                    comboNoteEl.classList.add('hidden');
                }

                for (const k of shown) {
                    const [a, b] = k.split('>');
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-1 bg-gray-50 rounded-lg px-2 py-1 text-sm';
                    div.innerHTML = `
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${a}</span>
                        <span class="text-gray-400">&gt;</span>
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${b}</span>
                    `;
                    comboWrap.appendChild(div);
                }
            }

            function updateRowHighlight(firstSet, secondSet) {
                horseGrid.querySelectorAll('tr[data-row]').forEach(tr => {
                    const no = tr.getAttribute('data-row');
                    const on = firstSet.has(no) || secondSet.has(no);
                    tr.classList.toggle('bg-gray-50', on);
                });
            }

            function updateAll() {
                const A = normalize(selected(firstSelector));
                const B = normalize(selected(secondSelector));

                firstInlineEl.textContent = A.length ? A.join(' / ') : '未選択';
                secondInlineEl.textContent = B.length ? B.join(' / ') : '未選択';

                let amount = parseInt(amountEl.value || '0', 10);
                if (Number.isNaN(amount)) amount = 0;
                if (amount < 100) amount = 100;
                amount = Math.floor(amount / 100) * 100;

                const keys = buildKeys(A, B);
                const t = keys.length;

                ticketsEl.textContent = t.toLocaleString('ja-JP');
                totalEl.textContent = (t * amount).toLocaleString('ja-JP');

                updateRowHighlight(new Set(A), new Set(B));
                renderCombos(keys);

                submitBtn.classList.toggle('opacity-50', t === 0);
            }

            // クリック補助（labelクリックは除外）
            horseGrid.addEventListener('click', (e) => {
                if (e.target.closest('input[type="checkbox"], label, button')) return;

                const td = e.target.closest('td[data-col][data-no]');
                if (!td) return;

                const col = td.getAttribute('data-col');
                const no = td.getAttribute('data-no');
                if (!col || !no) return;

                let sel = null;
                if (col === 'first') sel = firstSelector;
                if (col === 'second') sel = secondSelector;
                if (!sel) return;

                const cb = td.querySelector(`${sel}[value="${no}"]`);
                if (!cb) return;

                cb.checked = !cb.checked;
                updateAll();
            });

            horseGrid.addEventListener('change', (e) => {
                if (e.target.matches(firstSelector) || e.target.matches(secondSelector)) {
                    updateAll();
                }
            });

            function setAll(sel, checked) {
                horseGrid.querySelectorAll(sel).forEach(cb => cb.checked = checked);
                updateAll();
            }

            firstAllBtn.addEventListener('click', () => setAll(firstSelector, true));
            firstClearBtn.addEventListener('click', () => setAll(firstSelector, false));
            secondAllBtn.addEventListener('click', () => setAll(secondSelector, true));
            secondClearBtn.addEventListener('click', () => setAll(secondSelector, false));

            amountEl.addEventListener('input', updateAll);

            updateAll();
        })();
    </script>
</x-app-layout>
