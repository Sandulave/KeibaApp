<x-app-layout title="購入馬券作成（{{ $race->name }}）">
    @php
        // old() 復元（stringで統一）
        $oldAxis = (string) old('axis', '');
        $oldOpp = collect(old('opponents', []))->map(fn($v) => (string) $v)->all();
        $oldAmount = old('amount', 100);

        // Controllerから $horseNos が来る想定。未設定でも落とさない
        $horseNos = $horseNos ?? range(1, (int) ($race->horse_count ?? 18));
        $MAX_COMBO_PREVIEW = 200;

        // 枠色（box準拠）
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

        // 枠頭数配分（box準拠）
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

    {{-- この画面ではヘッダー右上（ログアウト/カート）を非表示（box準拠） --}}
    <style>
        header a[href*="/cart"],
        header form[action*="logout"],
        header button[type="submit"] {
            display: none !important;
        }

        /* この画面だけスコープ */
        #sanrentanOneAxisMulti .combo-scroll {
            scrollbar-width: thin;
        }

        #sanrentanOneAxisMulti .combo-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        #sanrentanOneAxisMulti .combo-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }

        #sanrentanOneAxisMulti .combo-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>

    <div id="sanrentanOneAxisMulti" class="space-y-4">

        <div class="flex items-center justify-between">
            <a href="{{ isset($betType) ? route('bet.modes', [$race, $betType]) : route('bet.types', $race) }}"
                class="text-sm text-blue-600 underline">
                ← 買い方選択に戻る
            </a>
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

            {{-- BuilderResolverが必要とする情報 --}}
            <input type="hidden" name="betType" value="{{ $betType ?? 'sanrentan' }}">
            <input type="hidden" name="mode" value="{{ $mode ?? 'oneaxis_multi' }}">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">三連単 1頭軸流し（マルチ）</h2>
                </div>

                <div class="text-sm text-gray-600 leading-relaxed">
                    軸馬を1頭、相手を2頭以上選びます。<br>
                    マルチなので、軸馬は1〜3着のどこに来ても当たる組合せを展開します（点数は通常の3倍になります）。
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- 左：馬一覧（表） --}}
                    <div class="lg:col-span-2">
                        <div class="flex items-end justify-between mb-2 gap-2">
                            <div>
                                <div class="text-sm font-medium">選択</div>
                                <div class="text-xs text-gray-500">軸は1頭、相手は2頭以上</div>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" id="axisClear"
                                    class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">
                                    軸クリア
                                </button>

                                <button type="button" id="oppAll"
                                    class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">
                                    相手 全通り
                                </button>

                                <button type="button" id="oppClear"
                                    class="px-3 py-2 rounded-lg bg-white border text-sm hover:bg-gray-100">
                                    相手 クリア
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-16">枠</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-14">馬番</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-40">馬名</th>

                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-36">軸</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-44">相手</th>
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

                                            <td class="px-2 py-2 font-semibold text-gray-900">
                                                {{ $noStr }}
                                            </td>
                                            <td class="px-3 py-2 !text-left text-base font-medium text-gray-800">
                                                {{ $horseNameByNo[$noStr] ?? '-' }}
                                            </td>

                                            {{-- 軸（radio） --}}
                                            <td class="px-3 py-2 cursor-pointer" data-col="axis"
                                                data-no="{{ $noStr }}">
                                                <label
                                                    class="inline-flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded hover:bg-gray-100
                                                           focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="radio" name="axis" value="{{ $noStr }}"
                                                        class="h-4 w-4 border-gray-300 text-indigo-600"
                                                        aria-label="軸：馬番{{ $noStr }}"
                                                        @checked($oldAxis === $noStr)>
                                                    <span class="text-gray-700">軸にする</span>
                                                </label>
                                            </td>

                                            {{-- 相手（checkbox） --}}
                                            <td class="px-3 py-2 cursor-pointer" data-col="opp"
                                                data-no="{{ $noStr }}">
                                                <label
                                                    class="inline-flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded hover:bg-gray-100
                                                           focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="opponents[]"
                                                        value="{{ $noStr }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                        aria-label="相手：馬番{{ $noStr }}"
                                                        @checked(in_array($noStr, $oldOpp, true))>
                                                    <span class="text-gray-700">相手</span>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 space-y-1">
                            @error('axis')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            @error('opponents')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            @error('opponents.*')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- 右：固定まとめ --}}
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">

                            <div class="rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden">
                                <div class="bg-indigo-600 text-white px-4 py-3">
                                    <div class="text-sm font-semibold">3連単 1頭軸マルチ</div>
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
                                            <div class="text-xs text-gray-500 mb-1">軸</div>
                                            <div id="selectedAxisInline" class="text-sm text-gray-800">未選択</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">相手</div>
                                            <div id="selectedOppInline" class="text-sm text-gray-800">未選択</div>
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

            const selectedAxisInlineEl = document.getElementById('selectedAxisInline');
            const selectedOppInlineEl = document.getElementById('selectedOppInline');

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

            // 軸を相手から除外（UIでもサーバでも除外するが、JS側でも安全に）
            function opponentsMinusAxis(axis, opp) {
                return opp.filter(v => v !== axis);
            }

            function tickets(axis, oppCount) {
                if (!axis) return 0;
                if (oppCount < 2) return 0;
                return 3 * oppCount * (oppCount - 1);
            }

            function buildCombos(axis, opp, limit = MAX_COMBO_PREVIEW) {
                const combos = [];
                let total = 0;

                if (!axis || opp.length < 2) {
                    return {
                        combos,
                        total
                    };
                }

                const m = opp.length;
                total = 3 * m * (m - 1);

                // (b,c) の順序あり（b != c）
                for (let i = 0; i < m; i++) {
                    for (let j = 0; j < m; j++) {
                        if (i === j) continue;

                        const b = opp[i];
                        const c = opp[j];

                        // 3通り（軸が1着/2着/3着）
                        const list = [
                            [axis, b, c],
                            [b, axis, c],
                            [b, c, axis],
                        ];

                        for (const x of list) {
                            if (combos.length < limit) combos.push(x);
                        }
                    }
                }

                return {
                    combos,
                    total
                };
            }

            function updateRowHighlight(axis, oppSet) {
                horseGrid.querySelectorAll('tr[data-row]').forEach(tr => {
                    const no = tr.getAttribute('data-row');
                    const on = (no === axis) || oppSet.has(no);
                    tr.classList.toggle('bg-gray-50', on);
                });
            }

            function syncAxisVsOpp(axis) {
                // 軸と同じ馬は相手にできない（UI上も無効化＆解除）
                horseGrid.querySelectorAll(oppSelector).forEach(cb => {
                    if (String(cb.value) === axis && axis) {
                        cb.checked = false;
                        cb.disabled = true;
                    } else {
                        cb.disabled = false;
                    }
                });
            }

            function renderCombos(axis, opp) {
                const {
                    combos,
                    total
                } = buildCombos(axis, opp, MAX_COMBO_PREVIEW);

                comboTotalEl.textContent = total.toLocaleString('ja-JP');
                comboWrap.innerHTML = '';

                if (!axis || opp.length < 2) {
                    comboShownEl.textContent = '0';
                    comboNoteEl.classList.add('hidden');
                    comboWrap.innerHTML = `
                        <div class="text-sm text-gray-400 text-center py-6">
                            軸を1頭、相手を2頭以上選ぶと表示されます
                        </div>
                    `;
                    return;
                }

                comboShownEl.textContent = combos.length.toLocaleString('ja-JP');

                if (total > MAX_COMBO_PREVIEW) {
                    comboNoteEl.classList.remove('hidden');
                    comboNoteEl.textContent =
                        `※表示は先頭 ${MAX_COMBO_PREVIEW.toLocaleString('ja-JP')} 点までです（全 ${total.toLocaleString('ja-JP')} 点）`;
                } else {
                    comboNoteEl.classList.add('hidden');
                }

                combos.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-1 bg-gray-50 rounded-lg px-2 py-1 text-sm';

                    div.innerHTML = `
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${c[0]}</span>
                        <span class="text-gray-400">›</span>
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${c[1]}</span>
                        <span class="text-gray-400">›</span>
                        <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${c[2]}</span>
                    `;
                    comboWrap.appendChild(div);
                });
            }

            function updateAll() {
                const axis = selectedAxis();
                const opp = normalize(selectedOpponents());
                const opp2 = opponentsMinusAxis(axis, opp);

                syncAxisVsOpp(axis);

                selectedAxisInlineEl.textContent = axis ? axis : '未選択';
                selectedOppInlineEl.textContent = opp2.length ? opp2.join(' / ') : '未選択';

                // 金額：100円単位 + 最低100
                let amount = parseInt(amountEl.value || '0', 10);
                if (Number.isNaN(amount)) amount = 0;
                if (amount < 100) amount = 100;
                amount = Math.floor(amount / 100) * 100;

                const t = tickets(axis, opp2.length);
                ticketsEl.textContent = t.toLocaleString('ja-JP');
                totalEl.textContent = (t * amount).toLocaleString('ja-JP');

                const oppSet = new Set(opp2);
                updateRowHighlight(axis, oppSet);
                renderCombos(axis, opp2);

                // 0点ならボタンを薄く（押せるがsubmitで止める）
                submitBtn.classList.toggle('opacity-50', t === 0);
            }

            // クリック補助（入力直クリック/labelクリックでは二重処理しない）
            horseGrid.addEventListener('click', (e) => {
                if (e.target.closest('input[type="checkbox"], input[type="radio"], label, button')) return;

                const td = e.target.closest('td[data-col][data-no]');
                if (!td) return;

                const col = td.getAttribute('data-col');
                const no = td.getAttribute('data-no');
                if (!col || !no) return;

                if (col === 'axis') {
                    const radio = td.querySelector(`${axisSelector}[value="${no}"]`);
                    if (!radio) return;
                    radio.checked = true;
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

            // change（キーボード操作も含む）
            horseGrid.addEventListener('change', (e) => {
                if (e.target.matches(axisSelector) || e.target.matches(oppSelector)) {
                    updateAll();
                }
            });

            // バルク操作
            axisClearBtn.addEventListener('click', () => {
                horseGrid.querySelectorAll(axisSelector).forEach(r => r.checked = false);
                updateAll();
            });

            oppAllBtn.addEventListener('click', () => {
                const axis = selectedAxis();
                horseGrid.querySelectorAll(oppSelector).forEach(cb => {
                    cb.checked = (String(cb.value) !== axis);
                });
                updateAll();
            });

            oppClearBtn.addEventListener('click', () => {
                horseGrid.querySelectorAll(oppSelector).forEach(cb => cb.checked = false);
                updateAll();
            });

            // submitガード（0点/重い点数）
            const formEl = amountEl.closest('form');
            formEl.addEventListener('submit', (e) => {
                const axis = selectedAxis();
                const opp = opponentsMinusAxis(axis, normalize(selectedOpponents()));
                const t = tickets(axis, opp.length);

                if (!axis) {
                    e.preventDefault();
                    alert('軸馬を1頭選んでください');
                    return;
                }
                if (opp.length < 2) {
                    e.preventDefault();
                    alert('相手を2頭以上選んでください');
                    return;
                }
            });

            // 金額：入力反映と丸め
            amountEl.addEventListener('input', updateAll);
            amountEl.addEventListener('blur', updateAll);

            updateAll();
        })();
    </script>
</x-app-layout>
