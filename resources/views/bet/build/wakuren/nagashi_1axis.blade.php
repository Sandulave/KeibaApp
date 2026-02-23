<x-app-layout title="購入馬券作成（{{ $race->name }}）">
    @php
        $oldAxis = (string) old('axis', '');
        $oldOpponents = collect(old('opponents', []))->map(fn($v) => (string) $v)->all();
        $oldAmount = old('amount', 100);

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

        // 画面は「枠」選択なので、存在する枠のみ
        $availableFrames = collect(range(1, 8))->filter(fn($f) => ($frameCounts[$f] ?? 0) > 0)->values()->all();

        // 枠 => 馬番一覧（表示用）
        $frameToHorses = [];
        foreach ($availableFrames as $f) {
            $frameToHorses[$f] = collect($horseToFrame)
                ->filter(fn($vf) => (int) $vf === (int) $f)
                ->keys()
                ->sort()
                ->values()
                ->all();
        }
    @endphp

    <style>
        #wakurenNagashi1 .combo-scroll {
            scrollbar-width: thin;
        }

        #wakurenNagashi1 .combo-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        #wakurenNagashi1 .combo-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }

        #wakurenNagashi1 .combo-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>

    <div id="wakurenNagashi1" class="space-y-4">

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

            <input type="hidden" name="betType" value="{{ $betType ?? 'wakuren' }}">
            <input type="hidden" name="mode" value="{{ $mode ?? 'nagashi_1axis' }}">

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4 space-y-4">

                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold">枠連 1頭軸流し</h2>
                </div>

                <div class="text-sm text-gray-600 leading-relaxed">
                    軸1枠 × 相手枠。<br>
                    枠連は順不同なので、表示は <strong>昇順（1-2）</strong> に固定します。<br>
                    ※同一枠（例：2-2）も発生し得るので、<strong>同一枠も生成</strong>します。
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- 左：枠一覧 --}}
                    <div class="lg:col-span-2">

                        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600 w-20">枠</th>
                                        <th class="px-3 py-2 text-center font-medium text-gray-600 w-28">軸</th>
                                        <th class="px-3 py-2 text-center font-medium text-gray-600 w-28">相手</th>
                                        <th class="px-3 py-2 text-left font-medium text-gray-600">含まれる馬番</th>
                                    </tr>
                                </thead>

                                <tbody id="frameGrid" class="divide-y divide-gray-100 bg-white">
                                    @foreach ($availableFrames as $f)
                                        @php
                                            $fStr = (string) $f;
                                            $frameClass =
                                                $frameColors[$f] ?? 'bg-gray-100 text-gray-900 ring-1 ring-gray-200';
                                            $horsesInFrame = $frameToHorses[$f] ?? [];
                                        @endphp

                                        <tr data-row="{{ $fStr }}" class="hover:bg-gray-50">
                                            <td class="px-3 py-2">
                                                <span
                                                    class="inline-flex w-10 h-8 items-center justify-center rounded-md text-xs font-bold {{ $frameClass }}">
                                                    {{ $fStr }}
                                                </span>
                                            </td>

                                            {{-- 軸（radio） --}}
                                            <td class="px-3 py-2 text-center cursor-pointer" data-col="axis"
                                                data-frame="{{ $fStr }}">
                                                <label
                                                    class="inline-flex items-center justify-center w-full py-2 rounded hover:bg-gray-100 cursor-pointer
                                                              focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="radio" name="axis" value="{{ $fStr }}"
                                                        class="h-4 w-4 border-gray-300 text-indigo-600"
                                                        @checked($oldAxis === $fStr)>
                                                </label>
                                            </td>

                                            {{-- 相手（checkbox） --}}
                                            <td class="px-3 py-2 text-center cursor-pointer" data-col="others"
                                                data-frame="{{ $fStr }}">
                                                <label
                                                    class="inline-flex items-center justify-center w-full py-2 rounded hover:bg-gray-100 cursor-pointer
                                                              focus-within:ring-2 focus-within:ring-indigo-500">
                                                    <input type="checkbox" name="opponents[]" value="{{ $fStr }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                                        @checked(in_array($fStr, $oldOpponents, true))>
                                                </label>
                                            </td>

                                            <td class="px-3 py-2 text-sm text-gray-700">
                                                {{ implode(' / ', array_map(fn($v) => (string) $v, $horsesInFrame)) }}
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

                    {{-- 右：まとめ --}}
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">

                            <div class="rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden">
                                <div class="bg-indigo-600 text-white px-4 py-3">
                                    <div class="text-sm font-semibold">枠連 1頭軸流し</div>
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
                                            <div id="axisInline" class="text-sm text-gray-800">未選択</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">相手</div>
                                            <div id="othersInline" class="text-sm text-gray-800">未選択</div>
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
                                                   px-4 py-3 text-white font-semibold shadow-md hover:opacity-90 transition">
                                        まとめてカートに追加
                                    </button>
                                </div>
                            </div>

                            {{-- プレビュー --}}
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

            const grid = document.getElementById('frameGrid');

            const amountEl = document.getElementById('amount');
            const ticketsEl = document.getElementById('tickets');
            const totalEl = document.getElementById('total');

            const axisInlineEl = document.getElementById('axisInline');
            const othersInlineEl = document.getElementById('othersInline');

            const comboWrap = document.getElementById('comboWrap');
            const comboShownEl = document.getElementById('comboShown');
            const comboTotalEl = document.getElementById('comboTotal');
            const comboNoteEl = document.getElementById('comboNote');

            const axisSel = 'input[name="axis"]';
            const othersSel = 'input[name="opponents[]"]';

            function getAxis() {
                const r = grid.querySelector(`${axisSel}:checked`);
                return r ? String(r.value) : '';
            }

            function getOthers() {
                return Array.from(grid.querySelectorAll(`${othersSel}:checked`)).map(i => String(i.value));
            }

            function normalizeAmount() {
                let amount = parseInt(amountEl.value || '0', 10);
                if (Number.isNaN(amount)) amount = 0;
                if (amount < 100) amount = 100;
                amount = Math.floor(amount / 100) * 100;
                return amount;
            }

            // ✅ 同一枠（2-2）も生成 / 順不同なので昇順 "a-b" 固定
            function buildKeys(axis, others) {
                if (!axis || others.length === 0) return [];

                const set = new Set();
                for (const o of others) {
                    const a = parseInt(axis, 10) || 0;
                    const b = parseInt(o, 10) || 0;
                    const key = (a <= b) ? `${axis}-${o}` : `${o}-${axis}`;
                    set.add(key);
                }

                return Array.from(set).sort((k1, k2) => {
                    const [a1, b1] = k1.split('-').map(n => parseInt(n, 10) || 0);
                    const [a2, b2] = k2.split('-').map(n => parseInt(n, 10) || 0);
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
                    軸と相手を選択してください
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
                    const [a, b] = k.split('-');
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-1 bg-gray-50 rounded-lg px-2 py-1 text-sm';
                    div.innerHTML = `
                <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${a}</span>
                <span class="text-gray-400">-</span>
                <span class="px-2 py-1 bg-white rounded border text-xs font-semibold">${b}</span>
            `;
                    comboWrap.appendChild(div);
                }
            }

            function updateAll() {
                const axis = getAxis();
                const others = getOthers();

                axisInlineEl.textContent = axis ? axis : '未選択';
                othersInlineEl.textContent = others.length ? others.join(' / ') : '未選択';

                const amount = normalizeAmount();
                const keys = buildKeys(axis, others);
                const t = keys.length;

                ticketsEl.textContent = t.toLocaleString('ja-JP');
                totalEl.textContent = (t * amount).toLocaleString('ja-JP');

                renderCombos(keys);
            }

            // ✅ セル周辺クリックで操作できるようにする（radio/checkbox両方）
            grid.addEventListener('click', (e) => {
                if (e.target.closest('input, label, button, a')) return;

                const td = e.target.closest('td[data-col][data-frame]');
                if (!td) return;

                const col = td.getAttribute('data-col');
                const frame = td.getAttribute('data-frame');
                if (!col || !frame) return;

                if (col === 'axis') {
                    const r = td.querySelector(`${axisSel}[value="${frame}"]`);
                    if (r) r.checked = true;
                    updateAll();
                    return;
                }

                if (col === 'others') {
                    const cb = td.querySelector(`${othersSel}[value="${frame}"]`);
                    if (cb) cb.checked = !cb.checked;
                    updateAll();
                    return;
                }
            });

            grid.addEventListener('change', (e) => {
                if (e.target.matches(axisSel) || e.target.matches(othersSel)) updateAll();
            });

            amountEl.addEventListener('input', updateAll);

            updateAll();
        })();
    </script>
</x-app-layout>
