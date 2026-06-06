<x-app-layout :title="$race->name . ' - 精算'">
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        @php
            $horseMax = (int) ($race->horse_count ?? config('domain.bet.default_horse_count', 18));
            $payoutMin = (int) config('domain.bet.payout.min', 100);
            $payoutStep = (int) config('domain.bet.payout.step', 10);
            $betTypes = config('domain.bet.type_labels', []);
        @endphp

        <div>
            <a href="{{ route('races.show', $race) }}" class="text-sm text-blue-600 hover:underline">← 詳細に戻る</a>
            <h1 class="mt-2 text-2xl font-bold">{{ $race->name }}：精算</h1>
        </div>

        @if (session('success'))
            <div class="rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded bg-red-100 p-3 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
            <h2 class="font-semibold">netkeibaから貼り付け</h2>
            <div class="mt-3 space-y-3">
                <textarea
                    id="netkeiba-paste"
                    rows="12"
                    class="block w-full rounded border-gray-300 text-sm"
                    placeholder="netkeibaの結果・払戻ページをコピーして貼り付け"></textarea>
                <div id="netkeiba-import-status" class="hidden rounded p-3 text-sm"></div>
                <div class="flex justify-end">
                    <button
                        type="button"
                        id="import-netkeiba"
                        class="rounded bg-purple-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-purple-700">
                        読み取ってフォームに反映
                    </button>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('races.settlement.update', $race) }}" class="space-y-6">
            @csrf

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
                <h2 class="font-semibold mb-3">レース結果（1〜3着・同着可）</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @for ($rank = 1; $rank <= 3; $rank++)
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <span class="font-medium">{{ $rank }}着</span>
                                <button type="button" class="clear-rank text-xs text-blue-600 hover:underline"
                                    data-rank="{{ $rank }}">
                                    全解除
                                </button>
                            </div>
                            <div class="grid grid-cols-6 sm:grid-cols-9 gap-2">
                                @for ($i = 1; $i <= $horseMax; $i++)
                                    <label class="block">
                                        <input type="checkbox" name="ranks[{{ $rank }}][]" value="{{ $i }}"
                                            @checked(in_array($i, old('ranks.' . $rank, $resultByRank[$rank] ?? [])))
                                            class="peer sr-only rank-checkbox rank-{{ $rank }}">
                                        <span
                                            class="block rounded border border-gray-300 px-1 py-1 text-center text-sm cursor-pointer select-none
                                                peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600
                                                hover:bg-blue-50">
                                            {{ $i }}
                                        </span>
                                    </label>
                                @endfor
                            </div>
                            @error('ranks.' . $rank)
                                <div class="mt-2 text-xs text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    @endfor
                </div>
                @error('ranks')
                    <div class="mt-2 text-xs text-red-600">{{ $message }}</div>
                @enderror
            </div>

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-semibold">取消馬</h2>
                    <button type="button" class="clear-withdrawals text-xs text-blue-600 hover:underline">全解除</button>
                </div>
                <div class="grid grid-cols-6 sm:grid-cols-9 gap-2">
                    @for ($i = 1; $i <= $horseMax; $i++)
                        <label class="block">
                            <input type="checkbox" name="withdrawals[]" value="{{ $i }}"
                                @checked(in_array($i, old('withdrawals', $withdrawals ?? [])))
                                class="peer sr-only withdrawal-checkbox">
                            <span
                                class="block rounded border border-gray-300 px-1 py-1 text-center text-sm cursor-pointer select-none
                                    peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600
                                    hover:bg-blue-50">
                                {{ $i }}
                            </span>
                        </label>
                    @endfor
                </div>
            </div>

            @php
                $oldPayouts = old('payouts', $payouts ?? []);
            @endphp

            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h2 class="font-semibold">払戻（100円あたり）</h2>
                    <button type="button" id="auto-fill-from-result"
                        class="rounded bg-emerald-100 px-3 py-1 text-sm text-emerald-800 hover:bg-emerald-200">
                        レース結果から当たり目を自動入力
                    </button>
                </div>

                @foreach ($betTypes as $betType => $label)
                    @php
                        $defaultRowCount = in_array($betType, ['fukusho', 'wide'], true) ? 3 : 1;
                        $rows = $oldPayouts[$betType] ?? [];
                        if (empty($rows)) {
                            $rows = array_fill(0, $defaultRowCount, ['selection_key' => '', 'payout_per_100' => '', 'popularity' => '']);
                        }
                    @endphp

                    <div class="mb-5 border rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-bold">{{ $label }}</h3>
                            <button type="button"
                                class="add-row rounded bg-blue-100 px-2 py-1 text-sm text-blue-700 hover:bg-blue-200"
                                data-bet="{{ $betType }}">
                                ＋ 行追加
                            </button>
                        </div>

                        <div class="space-y-2 payout-rows" data-bet="{{ $betType }}">
                            @foreach ($rows as $idx => $row)
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center payout-row">
                                    <input name="payouts[{{ $betType }}][{{ $idx }}][selection_key]"
                                        class="md:col-span-5 rounded border-gray-300 text-sm"
                                        placeholder="当たり目（例: 1-2 / 1>2>3）" value="{{ $row['selection_key'] ?? '' }}">

                                    <input type="number" min="{{ $payoutMin }}"
                                        name="payouts[{{ $betType }}][{{ $idx }}][payout_per_100]"
                                        step="{{ $payoutStep }}"
                                        class="md:col-span-3 rounded border-gray-300 text-sm" placeholder="払戻金"
                                        value="{{ $row['payout_per_100'] ?? '' }}">

                                    <input type="number" min="1"
                                        name="payouts[{{ $betType }}][{{ $idx }}][popularity]"
                                        class="md:col-span-2 rounded border-gray-300 text-sm" placeholder="人気"
                                        value="{{ $row['popularity'] ?? '' }}">

                                    <button type="button"
                                        class="remove-row md:col-span-2 rounded bg-gray-100 px-2 py-1 text-sm hover:bg-gray-200">
                                        削除
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <template id="tpl-{{ $betType }}">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center payout-row">
                                <input name="payouts[{{ $betType }}][__INDEX__][selection_key]"
                                    class="md:col-span-5 rounded border-gray-300 text-sm"
                                    placeholder="当たり目（例: 1-2 / 1>2>3）">
                                <input type="number" min="{{ $payoutMin }}" step="{{ $payoutStep }}" name="payouts[{{ $betType }}][__INDEX__][payout_per_100]"
                                    class="md:col-span-3 rounded border-gray-300 text-sm" placeholder="払戻金">
                                <input type="number" min="1" name="payouts[{{ $betType }}][__INDEX__][popularity]"
                                    class="md:col-span-2 rounded border-gray-300 text-sm" placeholder="人気">
                                <button type="button"
                                    class="remove-row md:col-span-2 rounded bg-gray-100 px-2 py-1 text-sm hover:bg-gray-200">
                                    削除
                                </button>
                            </div>
                        </template>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="rounded bg-blue-600 px-6 py-2 text-white shadow hover:bg-blue-700">保存</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const normalizeNum = (v) => String(parseInt(v, 10));
            const unordered2 = (a, b) => {
                const x = parseInt(a, 10);
                const y = parseInt(b, 10);
                return x <= y ? `${x}-${y}` : `${y}-${x}`;
            };
            const unordered3 = (a, b, c) => {
                const nums = [parseInt(a, 10), parseInt(b, 10), parseInt(c, 10)].sort((x, y) => x - y);
                return `${nums[0]}-${nums[1]}-${nums[2]}`;
            };
            const choose2 = (arr) => {
                const out = [];
                for (let i = 0; i < arr.length; i++) {
                    for (let j = i + 1; j < arr.length; j++) {
                        out.push([arr[i], arr[j]]);
                    }
                }
                return out;
            };
            const choose3 = (arr) => {
                const out = [];
                for (let i = 0; i < arr.length; i++) {
                    for (let j = i + 1; j < arr.length; j++) {
                        for (let k = j + 1; k < arr.length; k++) {
                            out.push([arr[i], arr[j], arr[k]]);
                        }
                    }
                }
                return out;
            };
            const unique = (arr) => [...new Set(arr)];
            const selectedByRank = (rank) => {
                return Array.from(document.querySelectorAll(`.rank-${rank}:checked`)).map((el) => normalizeNum(el.value));
            };
            const setStatus = (message, type = 'success') => {
                const status = document.getElementById('netkeiba-import-status');
                if (!status) {
                    return;
                }

                status.textContent = message;
                status.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-yellow-100', 'text-yellow-800', 'bg-red-100', 'text-red-800');
                if (type === 'error') {
                    status.classList.add('bg-red-100', 'text-red-800');
                } else if (type === 'warning') {
                    status.classList.add('bg-yellow-100', 'text-yellow-800');
                } else {
                    status.classList.add('bg-green-100', 'text-green-800');
                }
            };
            const setCheckedValues = (selector, values) => {
                const valueSet = new Set(values.map((value) => String(parseInt(value, 10))));
                document.querySelectorAll(selector).forEach((input) => {
                    input.checked = valueSet.has(String(parseInt(input.value, 10)));
                });
            };
            const renderPayoutRows = (betType, rows) => {
                const container = document.querySelector(`.payout-rows[data-bet="${betType}"]`);
                if (!container) {
                    return;
                }

                const rowsToRender = rows.length > 0 ? rows : [{ selection_key: '', payout_per_100: '', popularity: '' }];
                container.innerHTML = '';

                rowsToRender.forEach((data, idx) => {
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-1 md:grid-cols-12 gap-2 items-center payout-row';

                    const keyInput = document.createElement('input');
                    keyInput.name = `payouts[${betType}][${idx}][selection_key]`;
                    keyInput.className = 'md:col-span-5 rounded border-gray-300 text-sm';
                    keyInput.placeholder = '当たり目（例: 1-2 / 1>2>3）';
                    keyInput.value = data.selection_key ?? '';

                    const payoutInput = document.createElement('input');
                    payoutInput.type = 'number';
                    payoutInput.min = '{{ $payoutMin }}';
                    payoutInput.step = '{{ $payoutStep }}';
                    payoutInput.name = `payouts[${betType}][${idx}][payout_per_100]`;
                    payoutInput.className = 'md:col-span-3 rounded border-gray-300 text-sm';
                    payoutInput.placeholder = '払戻金';
                    payoutInput.value = data.payout_per_100 ?? '';

                    const popularityInput = document.createElement('input');
                    popularityInput.type = 'number';
                    popularityInput.min = '1';
                    popularityInput.name = `payouts[${betType}][${idx}][popularity]`;
                    popularityInput.className = 'md:col-span-2 rounded border-gray-300 text-sm';
                    popularityInput.placeholder = '人気';
                    popularityInput.value = data.popularity ?? '';

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'remove-row md:col-span-2 rounded bg-gray-100 px-2 py-1 text-sm hover:bg-gray-200';
                    removeButton.textContent = '削除';

                    row.append(keyInput, payoutInput, popularityInput, removeButton);
                    container.appendChild(row);
                });
            };
            const clearAndRenderRows = (betType, keys) => {
                const container = document.querySelector(`.payout-rows[data-bet="${betType}"]`);
                if (!container) {
                    return;
                }

                // 既存の入力値を selection_key 単位で退避（同キー重複時は先勝ち）
                const existingValues = new Map();
                container.querySelectorAll('.payout-row').forEach((row) => {
                    const keyInput = row.querySelector(`input[name^="payouts[${betType}]"][name$="[selection_key]"]`);
                    const payoutInput = row.querySelector(`input[name^="payouts[${betType}]"][name$="[payout_per_100]"]`);
                    const popularityInput = row.querySelector(`input[name^="payouts[${betType}]"][name$="[popularity]"]`);

                    if (!keyInput) {
                        return;
                    }

                    const key = keyInput.value.trim();
                    if (key !== '' && !existingValues.has(key)) {
                        existingValues.set(key, {
                            payout: payoutInput?.value ?? '',
                            popularity: popularityInput?.value ?? '',
                        });
                    }
                });

                const rowKeys = keys.length > 0 ? keys : [''];
                container.innerHTML = '';

                rowKeys.forEach((key, idx) => {
                    const preserved = existingValues.get(key) ?? {
                        payout: '',
                        popularity: '',
                    };
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-1 md:grid-cols-12 gap-2 items-center payout-row';
                    row.innerHTML = `
                        <input name="payouts[${betType}][${idx}][selection_key]" class="md:col-span-5 rounded border-gray-300 text-sm" placeholder="当たり目（例: 1-2 / 1>2>3）" value="${key}">
                        <input type="number" min="{{ $payoutMin }}" step="{{ $payoutStep }}" name="payouts[${betType}][${idx}][payout_per_100]" class="md:col-span-3 rounded border-gray-300 text-sm" placeholder="払戻金" value="${preserved.payout}">
                        <input type="number" min="1" name="payouts[${betType}][${idx}][popularity]" class="md:col-span-2 rounded border-gray-300 text-sm" placeholder="人気" value="${preserved.popularity}">
                        <button type="button" class="remove-row md:col-span-2 rounded bg-gray-100 px-2 py-1 text-sm hover:bg-gray-200">削除</button>
                    `;
                    container.appendChild(row);
                });
            };
            const betTypeMap = {
                '単勝': { key: 'tansho', picks: 1, ordered: false },
                '複勝': { key: 'fukusho', picks: 1, ordered: false },
                '枠連': { key: 'wakuren', picks: 2, ordered: false },
                '馬連': { key: 'umaren', picks: 2, ordered: false },
                'ワイド': { key: 'wide', picks: 2, ordered: false },
                '馬単': { key: 'umatan', picks: 2, ordered: true },
                '3連複': { key: 'sanrenpuku', picks: 3, ordered: false },
                '三連複': { key: 'sanrenpuku', picks: 3, ordered: false },
                '3連単': { key: 'sanrentan', picks: 3, ordered: true },
                '三連単': { key: 'sanrentan', picks: 3, ordered: true },
            };
            const payoutLabels = Object.keys(betTypeMap);
            const moneyValue = (line) => {
                const match = line.match(/([0-9,]+)\s*円/);
                return match ? parseInt(match[1].replaceAll(',', ''), 10) : null;
            };
            const popularityValue = (line) => {
                const match = line.match(/([0-9]+)\s*人気/);
                return match ? parseInt(match[1], 10) : null;
            };
            const plainIntValue = (line) => {
                const trimmed = String(line).trim();
                return /^[0-9]+$/.test(trimmed) ? parseInt(trimmed, 10) : null;
            };
            const numsInLine = (line) => Array.from(line.matchAll(/\d+/g)).map((match) => match[0]);
            const looksLikeHorseName = (line) => {
                const trimmed = String(line).trim();
                return trimmed !== '' && plainIntValue(trimmed) === null && !/[0-9]/.test(trimmed);
            };
            const normalizeSelection = (numbers, meta) => {
                const selected = numbers.slice(0, meta.picks).map((value) => String(parseInt(value, 10)));
                if (selected.length !== meta.picks || selected.some((value) => value === 'NaN')) {
                    return null;
                }

                if (meta.ordered) {
                    return selected.join('>');
                }

                return selected
                    .map((value) => parseInt(value, 10))
                    .sort((a, b) => a - b)
                    .join('-');
            };
            const parsePayoutRows = (blockLines, meta) => {
                const moneyIndex = blockLines.findIndex((line) => moneyValue(line) !== null);
                if (moneyIndex < 0) {
                    return [];
                }

                const selectionLines = blockLines.slice(0, moneyIndex).filter((line) => numsInLine(line).length > 0);
                const moneyLines = blockLines.slice(moneyIndex).filter((line) => moneyValue(line) !== null);
                const popularityLines = blockLines.slice(moneyIndex).filter((line) => popularityValue(line) !== null);
                const moneyCount = moneyLines.length;
                let selections = selectionLines
                    .map((line) => normalizeSelection(numsInLine(line), meta))
                    .filter((value) => value !== null);

                if (selections.length < moneyCount) {
                    const flatNumbers = selectionLines.flatMap((line) => numsInLine(line));
                    const grouped = [];
                    for (let i = 0; i < flatNumbers.length; i += meta.picks) {
                        const selection = normalizeSelection(flatNumbers.slice(i, i + meta.picks), meta);
                        if (selection !== null) {
                            grouped.push(selection);
                        }
                    }
                    selections = grouped;
                }

                return moneyLines.map((line, idx) => ({
                    selection_key: selections[idx] ?? '',
                    payout_per_100: moneyValue(line) ?? '',
                    popularity: popularityLines[idx] ? popularityValue(popularityLines[idx]) : '',
                })).filter((row) => row.selection_key !== '' || row.payout_per_100 !== '');
            };
            const parseNetkeibaText = (rawText) => {
                const lines = rawText
                    .split(/\r?\n/)
                    .map((line) => line.replace(/\u00a0/g, ' ').trim())
                    .filter((line) => line !== '');
                const warnings = [];
                const result = {
                    ranks: { 1: [], 2: [], 3: [] },
                    payouts: {},
                    warnings,
                };
                const resultStart = lines.findIndex((line) => line === '着順');
                const payoutStart = lines.findIndex((line) => line === '払い戻し' || line === '払戻');

                if (resultStart >= 0) {
                    const resultEnd = payoutStart >= 0 ? payoutStart : lines.length;
                    for (let i = resultStart + 1; i < resultEnd - 2; i++) {
                        const rank = plainIntValue(lines[i]);
                        const frame = plainIntValue(lines[i + 1]);
                        const horseNo = plainIntValue(lines[i + 2]);
                        if (
                            [1, 2, 3].includes(rank)
                            && frame !== null && frame >= 1 && frame <= 8
                            && horseNo !== null && horseNo >= 1 && horseNo <= {{ $horseMax }}
                            && looksLikeHorseName(lines[i + 3] ?? '')
                        ) {
                            result.ranks[rank].push(String(horseNo));
                        }
                    }
                    result.ranks[1] = unique(result.ranks[1]);
                    result.ranks[2] = unique(result.ranks[2]);
                    result.ranks[3] = unique(result.ranks[3]);
                } else {
                    warnings.push('着順欄が見つかりませんでした。');
                }

                if (payoutStart < 0) {
                    warnings.push('払い戻し欄が見つかりませんでした。');
                    return result;
                }

                const payoutEndCandidates = ['コーナー通過順位', 'ラップタイム', '馬場情報'];
                const payoutEnd = lines.findIndex((line, idx) => idx > payoutStart && payoutEndCandidates.includes(line));
                const payoutLines = lines.slice(payoutStart + 1, payoutEnd >= 0 ? payoutEnd : lines.length);

                payoutLabels.forEach((label) => {
                    const labelIndex = payoutLines.findIndex((line) => line === label);
                    if (labelIndex < 0) {
                        return;
                    }

                    const nextLabelIndex = payoutLines.findIndex((line, idx) => idx > labelIndex && payoutLabels.includes(line));
                    const blockLines = payoutLines.slice(labelIndex + 1, nextLabelIndex >= 0 ? nextLabelIndex : payoutLines.length);
                    const meta = betTypeMap[label];
                    const rows = parsePayoutRows(blockLines, meta);
                    if (rows.length > 0) {
                        result.payouts[meta.key] = rows;
                    } else {
                        warnings.push(`${label}を読み取れませんでした。`);
                    }
                });

                return result;
            };
            const applyNetkeibaResult = (result) => {
                [1, 2, 3].forEach((rank) => {
                    if ((result.ranks[rank] ?? []).length > 0) {
                        setCheckedValues(`.rank-${rank}`, result.ranks[rank]);
                    }
                });

                Object.entries(result.payouts).forEach(([betType, rows]) => {
                    renderPayoutRows(betType, rows);
                });
            };

            document.querySelectorAll('.clear-rank').forEach((button) => {
                button.addEventListener('click', () => {
                    const rank = button.dataset.rank;
                    document.querySelectorAll(`.rank-${rank}`).forEach((input) => {
                        input.checked = false;
                    });
                });
            });

            const clearWithdrawals = document.querySelector('.clear-withdrawals');
            if (clearWithdrawals) {
                clearWithdrawals.addEventListener('click', () => {
                    document.querySelectorAll('.withdrawal-checkbox').forEach((input) => {
                        input.checked = false;
                    });
                });
            }

            document.querySelectorAll('.add-row').forEach((button) => {
                button.addEventListener('click', () => {
                    const bet = button.dataset.bet;
                    const container = document.querySelector(`.payout-rows[data-bet="${bet}"]`);
                    const rowCount = container.querySelectorAll('.payout-row').length;
                    const template = document.getElementById(`tpl-${bet}`).content.cloneNode(true);

                    template.querySelectorAll('input').forEach((input) => {
                        input.name = input.name.replace('__INDEX__', String(rowCount));
                    });

                    container.appendChild(template);
                });
            });

            document.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement) || !target.classList.contains('remove-row')) {
                    return;
                }

                const container = target.closest('.payout-rows');
                if (!container) {
                    return;
                }

                if (container.querySelectorAll('.payout-row').length > 1) {
                    target.closest('.payout-row')?.remove();
                }
            });

            const autoFillButton = document.getElementById('auto-fill-from-result');
            if (autoFillButton) {
                autoFillButton.addEventListener('click', () => {
                    const first = unique(selectedByRank(1));
                    const second = unique(selectedByRank(2));
                    const third = unique(selectedByRank(3));
                    // 画像仕様に合わせ、並びは「着順優先」（1着→2着→3着）で保持する
                    // ※selection_key 自体の表記ルール（順不同系は昇順）は既存ロジックを維持
                    const top3 = unique([...first, ...second, ...third]);

                    // 単勝: 1着
                    clearAndRenderRows('tansho', first);
                    // 複勝: 1〜3着（同着含む）
                    clearAndRenderRows('fukusho', top3);

                    // 馬単: 1着 x 2着（順序あり）
                    const umatan = [];
                    first.forEach((a) => {
                        second.forEach((b) => {
                            if (a !== b) {
                                umatan.push(`${a}>${b}`);
                            }
                        });
                    });
                    clearAndRenderRows('umatan', unique(umatan));

                    // 馬連: 1着 x 2着（順不同）
                    const umaren = [];
                    first.forEach((a) => {
                        second.forEach((b) => {
                            if (a !== b) {
                                umaren.push(unordered2(a, b));
                            }
                        });
                    });
                    clearAndRenderRows('umaren', unique(umaren));

                    // ワイド: 1〜3着から2頭組み合わせ
                    clearAndRenderRows('wide', choose2(top3).map(([a, b]) => unordered2(a, b)));

                    // 三連単: 1着 x 2着 x 3着（順序あり）
                    const sanrentan = [];
                    first.forEach((a) => {
                        second.forEach((b) => {
                            if (a === b) return;
                            third.forEach((c) => {
                                if (c === a || c === b) return;
                                sanrentan.push(`${a}>${b}>${c}`);
                            });
                        });
                    });
                    clearAndRenderRows('sanrentan', unique(sanrentan));

                    // 三連複: 1〜3着から3頭組み合わせ（順不同）
                    clearAndRenderRows('sanrenpuku', unique(choose3(top3).map(([a, b, c]) => unordered3(a, b, c))));

                    // 枠連: 馬番→枠番の情報がこの画面に無いため自動入力対象外
                });
            }

            const importNetkeibaButton = document.getElementById('import-netkeiba');
            if (importNetkeibaButton) {
                importNetkeibaButton.addEventListener('click', () => {
                    const textarea = document.getElementById('netkeiba-paste');
                    const rawText = textarea?.value ?? '';
                    if (rawText.trim() === '') {
                        setStatus('貼り付け内容を入力してください。', 'error');
                        return;
                    }

                    const parsed = parseNetkeibaText(rawText);
                    const rankCount = [1, 2, 3].reduce((sum, rank) => sum + (parsed.ranks[rank]?.length ?? 0), 0);
                    const payoutTypeCount = Object.keys(parsed.payouts).length;
                    if (rankCount === 0 && payoutTypeCount === 0) {
                        setStatus(['読み取れる着順・払戻が見つかりませんでした。', ...parsed.warnings].join(' '), 'error');
                        return;
                    }

                    applyNetkeibaResult(parsed);
                    const message = `読み取り完了: 着順${rankCount}件、払戻${payoutTypeCount}券種をフォームに反映しました。保存前に内容を確認してください。`;
                    setStatus(parsed.warnings.length > 0 ? `${message} ${parsed.warnings.join(' ')}` : message, parsed.warnings.length > 0 ? 'warning' : 'success');
                });
            }
        });
    </script>
</x-app-layout>
