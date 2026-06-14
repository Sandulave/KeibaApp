<x-app-layout :title="$race->name . ' を編集'">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- 戻るリンク --}}
        <div class="mb-6">
            <a href="{{ route('races.index') }}"
               class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-700 hover:underline">
                <span aria-hidden="true">←</span>
                レース一覧に戻る
            </a>
        </div>

        {{-- ヘッダー --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight">
                {{ $race->name }}
            </h1>
            <p class="mt-2 text-sm text-gray-500">
                レース情報を編集してください。
            </p>
        </div>

        {{-- エラー表示 --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 p-4 ring-1 ring-green-200">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg bg-red-50 p-4 ring-1 ring-red-200">
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 p-4 ring-1 ring-red-200">
                <h3 class="text-sm font-medium text-red-800 mb-2">エラーが発生しました</h3>
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li class="text-sm text-red-700">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- フォーム --}}
        <form method="POST" action="{{ route('races.update', $race) }}" class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6">
            @csrf
            @method('PUT')

            {{-- 名前 --}}
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-900">
                    レース名 <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $race->name) }}"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 開催日 --}}
            <div class="mb-6">
                <label for="race_date" class="block text-sm font-medium text-gray-900">
                    開催日 <span class="text-red-500">*</span>
                </label>
                <input
                    type="date"
                    id="race_date"
                    name="race_date"
                    value="{{ old('race_date', $race->race_date) }}"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                @error('race_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 頭数 --}}
            <div class="mb-6">
                <label for="horse_count" class="block text-sm font-medium text-gray-900">
                    頭数 <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    id="horse_count"
                    name="horse_count"
                    value="{{ old('horse_count', $race->horse_count ?? 0) }}"
                    min="0"
                    max="18"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                @error('horse_count')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if (config('domain.site.type') === 'summer')
                <div class="mb-6 rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                    <p class="font-medium">配布金額は夏競馬ルールで自動設定されます。</p>
                    <p class="mt-1">G2: {{ number_format((int) config('domain.site.summer_allowances.g2', 10000)) }}円 / G3: {{ number_format((int) config('domain.site.summer_allowances.g3', 6000)) }}円</p>
                </div>
            @else
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="normal_allowance" class="block text-sm font-medium text-gray-900">
                            通常配布金額
                        </label>
                        <input
                            type="number"
                            id="normal_allowance"
                            name="normal_allowance"
                            value="{{ old('normal_allowance', (int) ($race->normal_allowance ?? 10000)) }}"
                            min="0"
                            step="100"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                        @error('normal_allowance')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="challenge_allowance" class="block text-sm font-medium text-gray-900">
                            勝負配布金額
                        </label>
                        <input
                            type="number"
                            id="challenge_allowance"
                            name="challenge_allowance"
                            value="{{ old('challenge_allowance', (int) ($race->challenge_allowance ?? 30000)) }}"
                            min="0"
                            step="100"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                        @error('challenge_allowance')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-medium text-gray-900">馬名（任意）</p>
                <p class="mt-1 text-xs text-gray-500">先に頭数を入力してください。</p>
                <div class="mt-3 rounded-lg border border-gray-200 bg-white p-3">
                    <p class="text-sm font-medium text-gray-900">netkeibaから馬名を貼り付け</p>
                    <textarea
                        id="netkeiba-horse-paste"
                        rows="8"
                        class="mt-2 block w-full rounded border-gray-300 text-sm"
                        placeholder="netkeibaの出馬表または結果ページをコピーして貼り付け"></textarea>
                    <div id="netkeiba-horse-import-status" class="mt-2 hidden rounded p-3 text-sm"></div>
                    <div class="mt-2 flex justify-end">
                        <button
                            type="button"
                            id="import-netkeiba-horses"
                            class="rounded bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">
                            馬名をフォームに反映
                        </button>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-2">
                    @for ($horseNo = 1; $horseNo <= 18; $horseNo++)
                        <label class="flex items-center gap-2 text-sm" data-horse-name-row="{{ $horseNo }}">
                            <span class="w-10 shrink-0 text-gray-600">{{ $horseNo }}番</span>
                            <input
                                type="text"
                                name="horse_names[{{ $horseNo }}]"
                                value="{{ old("horse_names.{$horseNo}", $horseNameByNo[$horseNo] ?? '') }}"
                                class="w-full rounded border-gray-300 text-sm"
                                placeholder="馬名">
                        </label>
                    @endfor
                </div>
                @error('horse_names')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('horse_names.*')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- コース --}}
            <div class="mb-6">
                <label for="course" class="block text-sm font-medium text-gray-900">
                    コース <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="course"
                    name="course"
                    value="{{ old('course', $race->course) }}"
                    required
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500">
                @error('course')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 結果（オプション） --}}
            <div class="mb-8">
                <label for="result" class="block text-sm font-medium text-gray-900">
                    結果 <span class="text-gray-500 text-xs">(オプション)</span>
                </label>
                <input
                    type="text"
                    id="result"
                    name="result"
                    value="{{ old('result', $race->result) }}"
                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:ring-blue-500">
                @error('result')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <label for="is_betting_closed" class="inline-flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        id="is_betting_closed"
                        name="is_betting_closed"
                        value="1"
                        @checked(old('is_betting_closed', $race->is_betting_closed))
                        class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500"
                    >
                    <span class="text-sm font-medium text-gray-900">投票終了にする（このレースの馬券購入を停止）</span>
                </label>
            </div>

            {{-- ボタン --}}
            <div class="flex gap-3">
                <button
                    type="submit"
                    class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700 transition-colors">
                    更新
                </button>
                <a
                    href="{{ route('races.index') }}"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-gray-900 font-medium hover:bg-gray-50 transition-colors text-center">
                    キャンセル
                </a>
            </div>
        </form>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const horseCountInput = document.getElementById('horse_count');
            const horseNameRows = Array.from(document.querySelectorAll('[data-horse-name-row]'));

            if (!horseCountInput || horseNameRows.length === 0) {
                return;
            }

            const normalizeDigits = (value) => String(value).replace(/[０-９]/g, (char) => {
                return String.fromCharCode(char.charCodeAt(0) - 0xFEE0);
            });
            const normalizeLine = (line) => {
                return normalizeDigits(line)
                    .replace(/\u00a0/g, ' ')
                    .replace(/\u3000/g, ' ')
                    .trim();
            };
            const plainIntValue = (line) => {
                const trimmed = normalizeLine(line);
                return /^[0-9]+$/.test(trimmed) ? Number.parseInt(trimmed, 10) : null;
            };
            const frameHorsePairValue = (line) => {
                const values = normalizeLine(line).match(/[0-9]+/g) ?? [];
                if (values.length !== 2) {
                    return null;
                }

                return {
                    frame: Number.parseInt(values[0], 10),
                    horseNo: Number.parseInt(values[1], 10),
                };
            };
            const ignoredHorseNameCells = new Set([
                '馬名',
                '性齢',
                '斤量',
                '騎手',
                'タイム',
                '着差',
                '人気',
                '単勝',
                'オッズ',
                '厩舎',
                '馬体重',
                '着順',
                '枠',
                '馬番',
                '印',
                '父',
                '母',
                '払い戻し',
                '払戻',
                '出馬表',
                '結果',
                '登録',
                '取消',
                '除外',
            ]);
            const cleanHorseName = (line) => {
                return normalizeLine(line)
                    .replace(/^[◎○▲△☆★注消\-−ー\s]+/u, '')
                    .replace(/^(取消|除外|競走除外)\s*/u, '')
                    .trim();
            };
            const looksLikeHorseName = (line) => {
                const trimmed = cleanHorseName(line);
                if (trimmed === '' || plainIntValue(trimmed) !== null || /[0-9]/.test(trimmed)) {
                    return false;
                }

                if (ignoredHorseNameCells.has(trimmed)) {
                    return false;
                }

                if (/^[牡牝セ騸][0-9]+$/u.test(trimmed) || /^[0-9]+(?:\.[0-9]+)?kg?$/i.test(trimmed)) {
                    return false;
                }

                return /[ァ-ヶー一-龠々〆ヵヶぁ-ん]/u.test(trimmed);
            };
            const setStatus = (message, type = 'success') => {
                const status = document.getElementById('netkeiba-horse-import-status');
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
            const parseNetkeibaHorseNames = (rawText) => {
                const lines = rawText
                    .split(/\r?\n/)
                    .map((line) => normalizeLine(line))
                    .filter((line) => line !== '');
                const namesByNo = new Map();
                const splitCells = (line) => {
                    return normalizeLine(line)
                        .split(/\t+|\s{2,}|\s+/)
                        .map((cell) => cell.trim())
                        .filter((cell) => cell !== '');
                };
                const looksLikeHorseDetail = (line) => {
                    return splitCells(line).some((cell) => /^[牡牝セ騸][0-9]+$/u.test(cell));
                };
                const setHorseName = (horseNo, horseName) => {
                    if (horseNo < 1 || horseNo > 18 || namesByNo.has(horseNo) || !looksLikeHorseName(horseName)) {
                        return false;
                    }

                    namesByNo.set(horseNo, cleanHorseName(horseName));
                    return true;
                };
                const firstHorseNameCell = (cells, startIndex) => {
                    for (let i = startIndex; i < Math.min(cells.length, startIndex + 5); i++) {
                        if (looksLikeHorseName(cells[i])) {
                            return cells[i];
                        }
                    }

                    return null;
                };
                const entryRowInfo = (line) => {
                    const cells = splitCells(line);
                    for (let i = 0; i < cells.length - 2; i++) {
                        const frame = plainIntValue(cells[i]);
                        const horseNo = plainIntValue(cells[i + 1]);
                        const horseName = firstHorseNameCell(cells, i + 2);
                        if (frame !== null && frame >= 1 && frame <= 8 && horseNo !== null && horseName !== null) {
                            return { horseNo, horseName };
                        }
                    }

                    for (let i = 0; i < cells.length - 3; i++) {
                        const rank = plainIntValue(cells[i]);
                        const frame = plainIntValue(cells[i + 1]);
                        const horseNo = plainIntValue(cells[i + 2]);
                        const horseName = firstHorseNameCell(cells, i + 3);
                        if (rank !== null && rank >= 1 && rank <= 18 && frame !== null && frame >= 1 && frame <= 8 && horseNo !== null && horseName !== null) {
                            return { horseNo, horseName };
                        }
                    }

                    return null;
                };
                const findEntryStart = () => {
                    for (let i = 0; i < lines.length; i++) {
                        const pair = frameHorsePairValue(lines[i]);
                        if (pair !== null && pair.frame >= 1 && pair.frame <= 8 && pair.horseNo >= 1 && pair.horseNo <= 18) {
                            for (let j = i + 1; j < Math.min(lines.length, i + 5); j++) {
                                if (looksLikeHorseName(lines[j]) && looksLikeHorseDetail(lines[j + 1] ?? '')) {
                                    return i;
                                }
                            }
                        }

                        const rowInfo = entryRowInfo(lines[i]);
                        if (rowInfo !== null && looksLikeHorseDetail(lines[i])) {
                            return i;
                        }
                    }

                    return 0;
                };
                const findEntryEnd = (startIndex) => {
                    const endMarkers = [
                        '選んだ馬のオッズを見る',
                        'Myレースに登録する',
                        'AIレース相性度',
                        '※結果・成績・オッズ',
                    ];
                    const endIndex = lines.findIndex((line, idx) => {
                        return idx > startIndex && endMarkers.some((marker) => line.includes(marker));
                    });

                    return endIndex >= 0 ? endIndex : lines.length;
                };
                const entryStart = findEntryStart();
                const entryLines = lines.slice(entryStart, findEntryEnd(entryStart));

                entryLines.forEach((line) => {
                    const cells = splitCells(line);
                    if (cells.length < 3) {
                        return;
                    }

                    for (let i = 0; i < cells.length - 2; i++) {
                        const frame = plainIntValue(cells[i]);
                        const horseNo = plainIntValue(cells[i + 1]);
                        const horseName = firstHorseNameCell(cells, i + 2);
                        if (frame !== null && frame >= 1 && frame <= 8 && horseNo !== null) {
                            setHorseName(horseNo, horseName ?? '');
                        }
                    }

                    for (let i = 0; i < cells.length - 3; i++) {
                        const rank = plainIntValue(cells[i]);
                        const frame = plainIntValue(cells[i + 1]);
                        const horseNo = plainIntValue(cells[i + 2]);
                        const horseName = firstHorseNameCell(cells, i + 3);
                        if (rank !== null && rank >= 1 && rank <= 18 && frame !== null && frame >= 1 && frame <= 8 && horseNo !== null) {
                            setHorseName(horseNo, horseName ?? '');
                        }
                    }
                });

                for (let i = 2; i < entryLines.length; i++) {
                    const frame = plainIntValue(entryLines[i - 2]);
                    const horseNo = plainIntValue(entryLines[i - 1]);
                    const horseName = entryLines[i];
                    const pair = frameHorsePairValue(entryLines[i - 2]);
                    const markedHorseName = entryLines[i];

                    if (
                        frame !== null && frame >= 1 && frame <= 8
                        && horseNo !== null && horseNo >= 1 && horseNo <= 18
                        && looksLikeHorseName(horseName)
                    ) {
                        setHorseName(horseNo, horseName);
                    }

                    if (
                        pair !== null
                        && pair.frame >= 1 && pair.frame <= 8
                        && pair.horseNo >= 1 && pair.horseNo <= 18
                        && looksLikeHorseName(markedHorseName)
                    ) {
                        setHorseName(pair.horseNo, markedHorseName);
                    }
                }

                return namesByNo;
            };
            const updateHorseNameRows = () => {
                const rawCount = Number.parseInt(horseCountInput.value, 10);
                const count = Number.isNaN(rawCount) ? 0 : Math.max(0, Math.min(18, rawCount));

                horseNameRows.forEach((row) => {
                    const rowNo = Number.parseInt(row.dataset.horseNameRow || '0', 10);
                    row.classList.toggle('hidden', rowNo > count);
                });
            };

            horseCountInput.addEventListener('input', updateHorseNameRows);
            updateHorseNameRows();

            const importButton = document.getElementById('import-netkeiba-horses');
            if (importButton) {
                importButton.addEventListener('click', () => {
                    const textarea = document.getElementById('netkeiba-horse-paste');
                    const rawText = textarea?.value ?? '';
                    if (rawText.trim() === '') {
                        setStatus('貼り付け内容を入力してください。', 'error');
                        return;
                    }

                    const namesByNo = parseNetkeibaHorseNames(rawText);
                    if (namesByNo.size === 0) {
                        setStatus('馬名を読み取れませんでした。netkeibaの出馬表または結果ページをコピーしてください。', 'error');
                        return;
                    }

                    let appliedCount = 0;
                    let maxHorseNo = 0;
                    namesByNo.forEach((horseName, horseNo) => {
                        const input = document.querySelector(`input[name="horse_names[${horseNo}]"]`);
                        if (!input) {
                            return;
                        }

                        input.value = horseName;
                        appliedCount++;
                        maxHorseNo = Math.max(maxHorseNo, horseNo);
                    });

                    const rawCurrentCount = Number.parseInt(horseCountInput.value, 10);
                    const currentCount = Number.isNaN(rawCurrentCount) ? 0 : rawCurrentCount;
                    if (maxHorseNo > currentCount) {
                        horseCountInput.value = String(maxHorseNo);
                    }
                    updateHorseNameRows();

                    const countMessage = maxHorseNo > currentCount
                        ? ` 頭数も${maxHorseNo}頭に更新しました。`
                        : '';
                    setStatus(`馬名${appliedCount}件をフォームに反映しました。保存前に内容を確認してください。${countMessage}`);
                });
            }
        });
    </script>
</x-app-layout>
