<x-app-layout title="購入馬券作成（{{ $race->name }}）">
    @php
        // old() で復元する用（stringで統一）
        $oldHorses = collect(old('horses', []))->map(fn($v) => (string) $v)->all();
        $oldAmount = old('amount', 100);
        $cartCount = count(session("bet_cart.{$race->id}.items", []));

        // Controllerから $horseNos が来る想定。万一未設定でも落とさない保険
        $horseNos = $horseNos ?? range(1, (int) ($race->horse_count ?? 18));

        // ─────────────────────────────────────────────
        // 枠色
        // ─────────────────────────────────────────────
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

        // ─────────────────────────────────────────────
        // 枠番の割り当て（出走頭数に応じたJRAルール）
        //
        // 16頭: 全枠2頭
        // 17頭: 8枠が3頭（他2頭）
        // 18頭: 7枠・8枠が3頭（他2頭）
        // 9～15頭: 8枠から内へ「2頭枠」を増やしていく（残り1頭枠）
        //
        // ※取消等で馬番が欠けても「詰め直し」しない前提：
        //   → そもそも horseNos の配列を“実在する馬番だけ”にすればOK。
        // ─────────────────────────────────────────────
        $horsesSorted = collect($horseNos)->map(fn($v) => (int) $v)->unique()->sort()->values()->all();

        $n = count($horsesSorted);

        $frameCounts = array_fill(1, 8, 0);

        if ($n <= 8) {
            // 1頭枠を内枠から
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = $f <= $n ? 1 : 0;
            }
        } elseif ($n <= 15) {
            // まず全枠1頭、余りを8枠から内へ +1 して2頭枠化
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 1;
            }
            $extra = $n - 8; // 1頭枠の上乗せ分

            for ($f = 8; $f >= 1 && $extra > 0; $f--) {
                $frameCounts[$f] += 1; // 2頭枠へ
                $extra--;
            }
        } else {
            // 16～18頭：まず全枠2頭、余りを8枠から内へ +1 して3頭枠化
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 2;
            }
            $extra = $n - 16; // 3頭枠の上乗せ分（0～2）

            for ($f = 8; $f >= 1 && $extra > 0; $f--) {
                $frameCounts[$f] += 1;
                $extra--;
            }
        }

        // horseNo => frame の対応表を作る（horseNos順ではなく、馬番昇順で枠に詰める）
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

    {{-- この画面ではヘッダー右上（ログアウト/カート）を非表示にする --}}
    <style>
        header a[href*="/cart"],
        header form[action*="logout"],
        header button[type="submit"] {
            display: none !important;
        }

        /* Firefox */
        .combo-scroll {
            scrollbar-width: thin;
        }

        /* Chrome / Edge */
        .combo-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .combo-scroll::-webkit-scrollbar-thumb {
            border-radius: 9999px;
            background: rgba(0, 0, 0, .18);
        }

        .combo-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, .06);
        }
    </style>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            {{-- $betType が無い呼び出しでも落とさない --}}
            <a href="{{ isset($betType) ? route('bet.modes', [$race, $betType]) : route('bet.types', $race) }}"
                class="text-sm text-blue-600 underline">
                ← 買い方選択に戻る
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
            <div class="text-sm text-gray-600">レース</div>
            <div class="font-semibold">{{ $race->name }}</div>
        </div>

        {{-- 三連単ボックス --}}
        <form method="POST" action="{{ route('bet.cart.add', $race) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="mode" value="sanrentan_box">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">三連単ボックス</h2>
                </div>

                <div class="text-sm text-gray-600 leading-relaxed">
                    馬番を複数選ぶと、三連単ボックスとして展開してカートに入れます。
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- 左：馬一覧（表） --}}
                    <div class="lg:col-span-2">
                        <div class="flex items-end justify-between mb-2">
                            <div>
                                <div class="text-sm font-medium">選ぶ馬番（3頭以上）</div>
                                <div class="text-xs text-gray-500">行クリックでも選択できます</div>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" id="clear"
                                    class="text-sm px-3 py-1.5 rounded border border-gray-300 hover:bg-gray-50">
                                    クリア
                                </button>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-lg ring-1 ring-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-16">枠</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-20">馬番</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">選択</th>
                                    </tr>
                                </thead>
                                <tbody id="horseGrid" class="divide-y divide-gray-100 bg-white">
                                    @foreach ($horsesSorted as $no)
                                        @php
                                            $frame = $horseToFrame[(int) $no] ?? 1;
                                            $frameClass =
                                                $frameColors[$frame] ??
                                                'bg-gray-100 text-gray-900 ring-1 ring-gray-200';
                                        @endphp

                                        <tr data-row="{{ $no }}" class="hover:bg-gray-50 cursor-pointer">
                                            <td class="px-3 py-2">
                                                <span
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-xs font-bold {{ $frameClass }}">
                                                    {{ $frame }}
                                                </span>
                                            </td>

                                            <td class="px-3 py-2 font-semibold text-gray-900">
                                                {{ $no }}
                                            </td>

                                            <td class="px-3 py-2">
                                                <label
                                                    class="inline-flex items-center gap-2 cursor-pointer select-none px-2 py-1 rounded hover:bg-gray-100">
                                                    <input type="checkbox" name="horses[]" value="{{ $no }}"
                                                        class="h-5 w-5 accent-gray-900" @checked(in_array((string) $no, $oldHorses, true))>
                                                    <span class="text-gray-700">選ぶ</span>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('horses')
                            <div class="text-sm text-red-600 mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- 右：まとめ枠 --}}
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">
                            {{-- 上：サマリー --}}
                            <div class="rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden">
                                <div class="bg-indigo-600 text-white px-4 py-3">
                                    <div class="text-sm font-semibold">3連単 ボックス</div>
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
                                        <div class="text-xs text-gray-500 mb-1">選択</div>
                                        <div id="selectedInline" class="text-sm text-gray-800">
                                            未選択
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

                                    <button
                                        class="w-full rounded-xl bg-gradient-to-r from-red-500 to-red-600
                                               px-4 py-3 text-white font-semibold shadow-md
                                               hover:opacity-90 transition">
                                        まとめてカートに追加
                                    </button>
                                </div>
                            </div>

                            {{-- 下：組み合わせプレビュー --}}
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
                                    class="combo-scroll h-[calc(100vh-360px)] min-h-72 overflow-auto space-y-2 rounded-lg border border-gray-200 p-2">
                                    <div class="text-sm text-gray-400 text-center py-6">
                                        3頭以上選ぶと表示されます
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
        const amountEl = document.getElementById('amount');
        const ticketsEl = document.getElementById('tickets');
        const totalEl = document.getElementById('total');

        const clearBtn = document.getElementById('clear');

        const horseGrid = document.getElementById('horseGrid');
        const horseSelector = 'input[name="horses[]"]';

        const selectedInlineEl = document.getElementById('selectedInline');

        const comboWrap = document.getElementById('comboWrap');
        const comboShownEl = document.getElementById('comboShown');
        const comboTotalEl = document.getElementById('comboTotal');
        const comboNoteEl = document.getElementById('comboNote');
        const MAX_COMBO_PREVIEW = 200; // 重くしないため、先頭だけ表示

        function selectedValues() {
            return Array.from(document.querySelectorAll(`${horseSelector}:checked`)).map(i => i.value);
        }

        function tickets(n) {
            if (n < 3) return 0;
            return n * (n - 1) * (n - 2);
        }

        function renderInline(values) {
            if (values.length === 0) {
                selectedInlineEl.textContent = '未選択';
                return;
            }
            selectedInlineEl.textContent = values.join(' / ');
        }

        function updatePreview() {
            const n = selectedValues().length;
            const t = tickets(n);
            const a = parseInt(amountEl.value || '0', 10) || 0;

            ticketsEl.textContent = t.toLocaleString('ja-JP');
            totalEl.textContent = (t * a).toLocaleString('ja-JP');
        }

        function updateRowHighlight() {
            const selected = new Set(selectedValues());
            horseGrid.querySelectorAll('tr[data-row]').forEach(tr => {
                const no = tr.getAttribute('data-row');
                tr.classList.toggle('bg-gray-50', selected.has(no));
            });
        }

        // ボックス＝順列（nP3）
        function buildCombos(values, limit = MAX_COMBO_PREVIEW) {
            const combos = [];
            const n = values.length;
            if (n < 3) return combos;

            for (let i = 0; i < n; i++) {
                for (let j = 0; j < n; j++) {
                    if (j === i) continue;
                    for (let k = 0; k < n; k++) {
                        if (k === i || k === j) continue;
                        combos.push([values[i], values[j], values[k]]);
                        if (combos.length >= limit) return combos;
                    }
                }
            }
            return combos;
        }

        function renderCombos(values) {
            const n = values.length;
            const total = tickets(n);

            comboTotalEl.textContent = total.toLocaleString('ja-JP');

            comboWrap.innerHTML = '';

            if (total === 0) {
                comboShownEl.textContent = '0';
                comboNoteEl.classList.add('hidden');
                comboWrap.innerHTML = `
                    <div class="text-sm text-gray-400 text-center py-6">
                        3頭以上選ぶと表示されます
                    </div>
                `;
                return;
            }

            const combos = buildCombos(values, MAX_COMBO_PREVIEW);
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
            const values = selectedValues();
            renderInline(values);
            updatePreview();
            updateRowHighlight();
            renderCombos(values);
        }

        horseGrid.addEventListener('change', (e) => {
            if (!e.target.matches(horseSelector)) return;
            updateAll();
        });

        // 行クリックでチェック切り替え（checkbox/labelクリックは二重処理しない）
        horseGrid.addEventListener('click', (e) => {
            if (e.target.closest('input[type="checkbox"], label')) return;

            const tr = e.target.closest('tr[data-row]');
            if (!tr) return;

            const cb = tr.querySelector('input[type="checkbox"][name="horses[]"]');
            if (!cb) return;

            cb.checked = !cb.checked;
            updateAll();
        });

        clearBtn.addEventListener('click', () => {
            document.querySelectorAll(horseSelector).forEach(cb => cb.checked = false);
            updateAll();
        });

        const formEl = amountEl.closest('form');
        formEl.addEventListener('submit', (e) => {
            const n = selectedValues().length;
            const t = tickets(n);

            if (n < 3) {
                e.preventDefault();
                alert('3頭以上選んでください');
                return;
            }
        });

        updateAll();

        // 入力中/フォーカスアウト時に集計だけ更新（値の書き戻しはしない）
        amountEl.addEventListener('input', updatePreview);
        amountEl.addEventListener('blur', updatePreview);
    </script>
</x-app-layout>
