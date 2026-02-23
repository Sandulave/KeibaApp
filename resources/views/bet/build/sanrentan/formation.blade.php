{{-- resources/views/bet/build/sanrentan/formation.blade.php --}}
<x-app-layout title="購入馬券作成（{{ $race->name }}）">
    @php
        // old() で復元する用（stringで統一）
        $oldFirst = collect(old('first', []))->map(fn($v) => (string) $v)->all();
        $oldSecond = collect(old('second', []))->map(fn($v) => (string) $v)->all();
        $oldThird = collect(old('third', []))->map(fn($v) => (string) $v)->all();
        $oldAmount = old('amount', 100);

        // Controllerから $horseNos が来る想定。万一未設定でも落とさない保険
        $horseNos = $horseNos ?? range(1, (int) ($race->horse_count ?? 18));

        // プレビュー/重さ制御（既存boxと同じ定数）
        $MAX_COMBO_PREVIEW = 200;

        // ─────────────────────────────────────────────
        // 枠色（既存boxと同じ）
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

        // horseNo を昇順ユニークで扱う（枠割り計算もこれ基準）
        $horsesSorted = collect($horseNos)->map(fn($v) => (int) $v)->unique()->sort()->values()->all();
        $n = count($horsesSorted);

        // ─────────────────────────────────────────────
        // 枠の頭数配分（既存boxと同じロジック）
        // ─────────────────────────────────────────────
        $frameCounts = array_fill(1, 8, 0);

        if ($n <= 8) {
            // 1頭枠を内枠から
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 0;
            }
            for ($i = 0; $i < $n; $i++) {
                $frameCounts[$i + 1] = 1;
            }
        } elseif ($n <= 15) {
            // 8枠から内へ 2頭枠を増やす（残りは1頭枠）
            for ($f = 1; $f <= 8; $f++) {
                $frameCounts[$f] = 1;
            }
            $extra = $n - 8; // 2頭枠化する分
            for ($f = 8; $f >= 1 && $extra > 0; $f--) {
                $frameCounts[$f] += 1;
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

        // horseNo => frame の対応表を作る（馬番昇順で枠に詰める）
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

    {{-- この画面ではヘッダー右上（ログアウト/カート）を非表示にする（既存box準拠） --}}
    <style>
        header a[href*="/cart"],
        header form[action*="logout"],
        header button[type="submit"] {
            display: none !important;
        }

        /* この画面だけスコープ */
        #sanrentanFormation .combo-scroll::-webkit-scrollbar {
            width: 8px;
        }

        #sanrentanFormation .combo-scroll::-webkit-scrollbar-thumb {
            border-radius: 9999px;
            background: rgba(0, 0, 0, .18);
        }

        #sanrentanFormation .combo-scroll::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, .06);
        }
    </style>

    <div id="sanrentanFormation" class="space-y-4">
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
            <input type="hidden" name="mode" value="formation">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">三連単フォーメーション</h2>
                </div>

                <div class="text-sm text-gray-600 leading-relaxed">
                    1着・2着・3着に入れる馬番を選びます。<br>
                    同一組み合わせ内で同じ馬番になるケース（例：1着=2着）は、自動的に除外して点数計算します。
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- 左：表 --}}
                    <div class="lg:col-span-2">
                        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-16">枠</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-20">馬番</th>

                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-44">
                                            <div class="space-y-1">
                                                <div class="font-semibold text-gray-700">1着</div>
                                                <div class="flex gap-1">
                                                    <button type="button"
                                                        class="px-2 py-1 rounded bg-white border text-xs hover:bg-gray-100"
                                                        data-bulk="all" data-target="first">全通り</button>
                                                    <button type="button"
                                                        class="px-2 py-1 rounded bg-white border text-xs hover:bg-gray-100"
                                                        data-bulk="clear" data-target="first">クリア</button>
                                                </div>
                                            </div>
                                        </th>

                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-44">
                                            <div class="space-y-1">
                                                <div class="font-semibold text-gray-700">2着</div>
                                                <div class="flex gap-1">
                                                    <button type="button"
                                                        class="px-2 py-1 rounded bg-white border text-xs hover:bg-gray-100"
                                                        data-bulk="all" data-target="second">全通り</button>
                                                    <button type="button"
                                                        class="px-2 py-1 rounded bg-white border text-xs hover:bg-gray-100"
                                                        data-bulk="clear" data-target="second">クリア</button>
                                                </div>
                                            </div>
                                        </th>

                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-44">
                                            <div class="space-y-1">
                                                <div class="font-semibold text-gray-700">3着</div>
                                                <div class="flex gap-1">
                                                    <button type="button"
                                                        class="px-2 py-1 rounded bg-white border text-xs hover:bg-gray-100"
                                                        data-bulk="all" data-target="third">全通り</button>
                                                    <button type="button"
                                                        class="px-2 py-1 rounded bg-white border text-xs hover:bg-gray-100"
                                                        data-bulk="clear" data-target="third">クリア</button>
                                                </div>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>

                                <tbody id="horseGrid" class="divide-y divide-gray-100 bg-white">
                                    @foreach ($horsesSorted as $no)
                                        @php
                                            $frame = $horseToFrame[(int) $no] ?? 1;
                                            $frameClass = $frameColors[$frame] ?? $frameColors[1];
                                            $noStr = (string) $no;
                                        @endphp

                                        <tr data-row="{{ $noStr }}" class="hover:bg-gray-50 cursor-pointer">
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
                                            <td class="px-3 py-2" data-col="first" data-no="{{ $noStr }}">
                                                <label
                                                    class="inline-flex items-center justify-center cursor-pointer select-none
                                                              px-3 py-2 rounded hover:bg-gray-100
                                                              focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="first[]" value="{{ $noStr }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                        aria-label="1着：馬番{{ $noStr }}"
                                                        @checked(in_array($noStr, $oldFirst, true))>
                                                </label>
                                            </td>

                                            {{-- 2着 --}}
                                            <td class="px-3 py-2" data-col="second" data-no="{{ $noStr }}">
                                                <label
                                                    class="inline-flex items-center justify-center cursor-pointer select-none
                                                              px-3 py-2 rounded hover:bg-gray-100
                                                              focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="second[]" value="{{ $noStr }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                        aria-label="2着：馬番{{ $noStr }}"
                                                        @checked(in_array($noStr, $oldSecond, true))>
                                                </label>
                                            </td>

                                            {{-- 3着 --}}
                                            <td class="px-3 py-2" data-col="third" data-no="{{ $noStr }}">
                                                <label
                                                    class="inline-flex items-center justify-center cursor-pointer select-none
                                                              px-3 py-2 rounded hover:bg-gray-100
                                                              focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="third[]" value="{{ $noStr }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                        aria-label="3着：馬番{{ $noStr }}"
                                                        @checked(in_array($noStr, $oldThird, true))>
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- 配列系エラー表示（必要最低限） --}}
                        <div class="mt-3 space-y-1">
                            @error('first')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            @error('second')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            @error('third')
                                <div class="text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- 右：まとめ枠 --}}
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">
                            {{-- 上：サマリー --}}
                            <div class="rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden">
                                <div class="bg-indigo-600 text-white px-4 py-3">
                                    <div class="text-sm font-semibold">3連単 フォーメーション</div>
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
                                            <div id="selectedFirstInline" class="text-sm text-gray-800">未選択</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">2着</div>
                                            <div id="selectedSecondInline" class="text-sm text-gray-800">未選択</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">3着</div>
                                            <div id="selectedThirdInline" class="text-sm text-gray-800">未選択</div>
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

                                    <button type="submit"
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
                                    class="combo-scroll h-[calc(100vh-360px)] min-h-72 overflow-auto
                                           space-y-2 rounded-lg border border-gray-200 p-2">
                                    <div class="text-sm text-gray-400 text-center py-6">
                                        1着・2着・3着を選ぶと表示されます
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
            const amountEl = document.getElementById('amount');
            const ticketsEl = document.getElementById('tickets');
            const totalEl = document.getElementById('total');

            const selectedFirstInlineEl = document.getElementById('selectedFirstInline');
            const selectedSecondInlineEl = document.getElementById('selectedSecondInline');
            const selectedThirdInlineEl = document.getElementById('selectedThirdInline');

            const comboWrap = document.getElementById('comboWrap');
            const comboShownEl = document.getElementById('comboShown');
            const comboTotalEl = document.getElementById('comboTotal');
            const comboNoteEl = document.getElementById('comboNote');
            const MAX_COMBO_PREVIEW = {{ (int) $MAX_COMBO_PREVIEW }};

            const horseGrid = document.getElementById('horseGrid');

            const selFirst = 'input[name="first[]"]';
            const selSecond = 'input[name="second[]"]';
            const selThird = 'input[name="third[]"]';

            function normalize(values) {
                const uniq = Array.from(new Set(values.map(v => String(v).trim()).filter(v => v !== '')));
                // 表示と組み合わせ順は昇順が読みやすい
                uniq.sort((a, b) => (parseInt(a, 10) || 0) - (parseInt(b, 10) || 0));
                return uniq;
            }

            function selectedValues(selector) {
                return Array.from(horseGrid.querySelectorAll(`${selector}:checked`)).map(i => i.value);
            }

            function countTickets(A, B, C) {
                let total = 0;
                for (const a of A) {
                    for (const b of B) {
                        if (b === a) continue;
                        for (const c of C) {
                            if (c === a || c === b) continue;
                            total++;
                        }
                    }
                }
                return total;
            }

            function buildCombos(A, B, C, limit) {
                const combos = [];
                let total = 0;

                for (const a of A) {
                    for (const b of B) {
                        if (b === a) continue;
                        for (const c of C) {
                            if (c === a || c === b) continue;

                            total++;
                            if (combos.length < limit) {
                                combos.push([a, b, c]);
                            }
                        }
                    }
                }
                return {
                    combos,
                    total
                };
            }

            function renderInline(A, B, C) {
                selectedFirstInlineEl.textContent = A.length ? A.join(' / ') : '未選択';
                selectedSecondInlineEl.textContent = B.length ? B.join(' / ') : '未選択';
                selectedThirdInlineEl.textContent = C.length ? C.join(' / ') : '未選択';
            }

            function updateRowHighlight(A, B, C) {
                const selected = new Set([...A, ...B, ...C]);
                horseGrid.querySelectorAll('tr[data-row]').forEach(tr => {
                    const no = tr.getAttribute('data-row');
                    tr.classList.toggle('bg-gray-50', selected.has(no));
                });
            }

            function renderCombos(A, B, C) {
                const {
                    combos,
                    total
                } = buildCombos(A, B, C, MAX_COMBO_PREVIEW);

                comboTotalEl.textContent = total.toLocaleString('ja-JP');
                comboWrap.innerHTML = '';

                if (total === 0) {
                    comboShownEl.textContent = '0';
                    comboNoteEl.classList.add('hidden');
                    comboWrap.innerHTML = `
                        <div class="text-sm text-gray-400 text-center py-6">
                            1着・2着・3着を選ぶと表示されます
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

            function updatePreview(A, B, C) {
                const t = countTickets(A, B, C);

                ticketsEl.textContent = t.toLocaleString('ja-JP');

                // amount: 100円単位 + 最低100
                let amount = parseInt(amountEl.value || '0', 10);
                if (Number.isNaN(amount)) amount = 0;
                if (amount < 100) amount = 100;
                amount = Math.floor(amount / 100) * 100;

                totalEl.textContent = (t * amount).toLocaleString('ja-JP');
            }

            function updateAll() {
                const A = normalize(selectedValues(selFirst));
                const B = normalize(selectedValues(selSecond));
                const C = normalize(selectedValues(selThird));

                renderInline(A, B, C);
                updatePreview(A, B, C);
                updateRowHighlight(A, B, C);
                renderCombos(A, B, C);
            }

            // change: checkbox操作
            horseGrid.addEventListener('change', (e) => {
                if (!e.target.matches(selFirst) && !e.target.matches(selSecond) && !e.target.matches(selThird))
                    return;
                updateAll();
            });

            // 行クリック補助（checkbox/labelクリックは二重処理しない）
            // → クリックしたセル（data-col）のcheckboxだけトグルする
            horseGrid.addEventListener('click', (e) => {
                if (e.target.closest('input[type="checkbox"], label, button')) return;

                const td = e.target.closest('td[data-col][data-no]');
                if (!td) return;

                const cb = td.querySelector('input[type="checkbox"]');
                if (!cb) return;

                cb.checked = !cb.checked;
                updateAll();
            });

            // 全通り/クリア
            document.querySelectorAll('#sanrentanFormation button[data-bulk]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const bulk = btn.getAttribute('data-bulk'); // all / clear
                    const target = btn.getAttribute('data-target'); // first / second / third
                    const name = `${target}[]`;

                    horseGrid.querySelectorAll(`input[name="${name}"]`).forEach(cb => {
                        cb.checked = (bulk === 'all');
                    });

                    updateAll();
                });
            });

            const formEl = amountEl.closest('form');
            formEl.addEventListener('submit', (e) => {
                const A = normalize(selectedValues(selFirst));
                const B = normalize(selectedValues(selSecond));
                const C = normalize(selectedValues(selThird));

                const t = countTickets(A, B, C);
                if (t <= 0) {
                    alert('有効な買い目がありません（同一馬の重複を除外した結果0点です）');
                    e.preventDefault();
                    return;
                }
            });

            // 入力中も反映（100円単位はblurで揃えるのはboxと同じ）
            amountEl.addEventListener('input', () => updateAll());
            amountEl.addEventListener('blur', () => updateAll());

            updateAll();
        })();
    </script>
</x-app-layout>
