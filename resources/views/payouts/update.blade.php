<x-app-layout :title="$race->name . ' - 配当'">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        @php
            $betTypes = \App\Enums\BetType::all();
            $pad2 = function ($v) {
                $v = trim((string) $v);
                if ($v === '') {
                    return '';
                }
                $v = preg_replace('/[^0-9]/', '', $v);
                if ($v === '') {
                    return '';
                }
                return str_pad($v, 2, '0', STR_PAD_LEFT);
            };
        @endphp


        <div>
            <a href="{{ route('races.show', $race) }}" class="text-sm text-blue-600 hover:underline">
                ← 詳細に戻る
            </a>
            <h1 class="mt-2 text-2xl font-bold">{{ $race->name }}：配当</h1>
            <p class="text-sm text-gray-500 mt-1">100円あたりの払戻金を登録します※同着に対応しています</p>
            <p class="text-sm text-gray-500 mt-1">返還は馬券購入側で登録します</p>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 ring-1 ring-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif



        @php
            $horseOptions = [];
            for ($n = 1; $n <= 18; $n++) {
                $horseOptions[] = str_pad((string) $n, 2, '0', STR_PAD_LEFT);
            }
        @endphp


        <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4">券種ごとの配当（複数同時保存）</h2>

            <form method="POST" action="{{ route('races.payouts.store', $race) }}" class="space-y-8" id="payout-form">
                @csrf

                <div class="flex flex-col gap-8">
                    @foreach ($betTypes as $bt)
                        @php
                            // Enumインスタンス化
                            $btEnum = is_string($bt) ? BetType::tryFrom($bt) : $bt;
                            if (!$btEnum) {
                                continue;
                            }
                            $btKey = $btEnum->value;
                        @endphp
                        @php
                            // old優先（バリデーションエラー後）
                            $existingRows = old('payouts.' . $btKey);

                            // oldが無い場合はDBから
                            if (is_null($existingRows)) {
                                $existingRows = $race->payouts
                                    ->where('bet_type', $btKey)
                                    ->map(function ($p) use ($pad2) {
                                        $key = (string) ($p->selection_key ?? '');
                                        $parts = preg_split('/[-,>]/', $key) ?: [];
                                        $parts = array_values(
                                            array_filter(array_map('trim', $parts), fn($v) => $v !== ''),
                                        );

                                        return [
                                            'a' => $pad2($parts[0] ?? ''),
                                            'b' => $pad2($parts[1] ?? ''),
                                            'c' => $pad2($parts[2] ?? ''),
                                            'payout_per_100' => (string) ($p->payout_per_100 ?? ''),
                                        ];
                                    })
                                    ->values()
                                    ->toArray();
                            }

                            // oldの形は「selection_key string」が基本（hiddenで送るため）
                            // ただし過去の形が混ざってても耐える
                            if (is_array($existingRows) && isset($existingRows[0]) && is_array($existingRows[0])) {
                                $existingRows = array_map(function ($row) use ($pad2) {
                                    $key = $row['selection_key'] ?? '';
                                    $pay = $row['payout_per_100'] ?? '';

                                    if (is_array($key)) {
                                        $a = $pad2($key[0] ?? '');
                                        $b = $pad2($key[1] ?? '');
                                        $c = $pad2($key[2] ?? '');
                                    } else {
                                        $parts = preg_split('/[-,>]/', (string) $key) ?: [];
                                        $parts = array_values(
                                            array_filter(array_map('trim', $parts), fn($v) => $v !== ''),
                                        );
                                        $a = $pad2($parts[0] ?? '');
                                        $b = $pad2($parts[1] ?? '');
                                        $c = $pad2($parts[2] ?? '');
                                    }

                                    // a/b/cで揃える
                                    return [
                                        'a' => $row['a'] ?? $a,
                                        'b' => $row['b'] ?? $b,
                                        'c' => $row['c'] ?? $c,
                                        'payout_per_100' => (string) $pay,
                                    ];
                                }, $existingRows);
                            }

                            // 最低1件（複勝・ワイドは3件、他は1件）
                            if (empty($existingRows)) {
                                if (in_array($btKey, ['fukusho', 'wide'])) {
                                    $existingRows = [
                                        ['a' => '', 'b' => '', 'c' => '', 'payout_per_100' => ''],
                                        ['a' => '', 'b' => '', 'c' => '', 'payout_per_100' => ''],
                                        ['a' => '', 'b' => '', 'c' => '', 'payout_per_100' => ''],
                                    ];
                                } else {
                                    $existingRows = [['a' => '', 'b' => '', 'c' => '', 'payout_per_100' => '']];
                                }
                            }
                        @endphp

                        <div class="bg-gray-50 rounded-lg border border-gray-200 p-4" x-data="{
                            betType: '{{ $btKey }}',
                            rows: @js($existingRows),

                            blankRow() {
                                return { a: '', b: '', c: '', payout_per_100: '' };
                            },

                            addRow() {
                                this.rows.push(this.blankRow());
                            },

                            removeRow(idx) {
                                if (this.rows.length > 1) this.rows.splice(idx, 1);
                            },

                            buildSelectionKey(row) {
                                const pad = v => v ? String(v).padStart(2, '0') : '';
                                const a = pad(row.a);
                                const b = pad(row.b);
                                const c = pad(row.c);

                                // Enumの仕様で分岐
                                if (['tansho', 'fukusho'].includes(this.betType)) {
                                    return a;
                                }
                                if (this.betType === 'umatan') {
                                    return (a && b) ? `${a}>${b}` : '';
                                }
                                if (this.betType === 'sanrentan') {
                                    return (a && b && c) ? `${a}>${b}>${c}` : '';
                                }
                                if (this.betType === 'sanrenpuku') {
                                    return (a && b && c) ? `${a}-${b}-${c}` : '';
                                }
                                // umaren / wide / wakuren
                                return (a && b) ? `${a}-${b}` : '';
                            }
                        }">
                            <div class="flex items-center mb-4">
                                <div class="text-lg font-bold text-blue-800 mr-2">{{ $btEnum->label() }}</div>
                            </div>

                            <div class="flex items-start gap-3 items-stretch">
                                <div class="flex flex-wrap gap-4">
                                    <template x-for="(row, i) in rows" :key="i">
                                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 flex flex-col items-center justify-center relative overflow-hidden"
                                            :class="(betType === 'fukusho' || betType === 'wide') ? 'w-48' : 'w-72'">

                                            <label class="block text-xs text-gray-500 mb-1">馬番</label>

                                            {{-- 券種に応じて select の数を変える --}}
                                            @if (in_array($btKey, ['tansho', 'fukusho']))
                                                <div class="grid grid-cols-1 justify-items-center w-full">
                                                    <select class="w-16 border rounded px-2 py-1" x-model="row.a"
                                                        :class="row.a ? 'bg-blue-200' : ''">
                                                        <option value="">—</option>
                                                        @foreach ($horseOptions as $v)
                                                            <option value="{{ $v }}">{{ $v }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                </div>
                                            @elseif(in_array($btKey, ['sanrenpuku', 'sanrentan']))
                                                <div class="grid grid-cols-3 gap-2 justify-items-center w-full">
                                                    <select class="w-16 border rounded px-2 py-1" x-model="row.a"
                                                        :class="row.a ? 'bg-blue-200' : ''">
                                                        <option value="">—</option>
                                                        @foreach ($horseOptions as $v)
                                                            <option value="{{ $v }}">{{ $v }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <select class="w-16 border rounded px-2 py-1" x-model="row.b"
                                                        :class="row.b ? 'bg-blue-200' : ''">
                                                        <option value="">—</option>
                                                        @foreach ($horseOptions as $v)
                                                            <option value="{{ $v }}">{{ $v }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <select class="w-16 border rounded px-2 py-1" x-model="row.c"
                                                        :class="row.c ? 'bg-blue-200' : ''">
                                                        <option value="">—</option>
                                                        @foreach ($horseOptions as $v)
                                                            <option value="{{ $v }}">{{ $v }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                </div>
                                            @else
                                                <div class="grid grid-cols-2 gap-2 justify-items-center w-full">
                                                    <select class="w-16 border rounded px-2 py-1" x-model="row.a"
                                                        :class="row.a ? 'bg-blue-200' : ''">
                                                        <option value="">—</option>
                                                        @foreach ($horseOptions as $v)
                                                            <option value="{{ $v }}">{{ $v }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <select class="w-16 border rounded px-2 py-1" x-model="row.b"
                                                        :class="row.b ? 'bg-blue-200' : ''">
                                                        <option value="">—</option>
                                                        @foreach ($horseOptions as $v)
                                                            <option value="{{ $v }}">{{ $v }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                </div>
                                            @endif

                                            {{-- hidden: selection_key を自動生成して送信 --}}
                                            <input type="hidden"
                                                :name="'payouts[{{ $btKey }}][' + i + '][selection_key]'"
                                                :value="buildSelectionKey(row)">

                                            <label class="block text-xs text-gray-500 mt-3 mb-1">払戻金</label>
                                            <input type="number"
                                                :name="'payouts[{{ $btKey }}][' + i + '][payout_per_100]'"
                                                x-model="row.payout_per_100" inputmode="numeric" placeholder="例: 12340"
                                                step="10" :class="row.payout_per_100 ? 'bg-blue-200' : ''"
                                                class="border rounded px-2 py-1 w-24 text-center mx-auto">

                                            <button type="button" @click="removeRow(i)"
                                                class="absolute top-2 right-2 text-gray-400 hover:text-red-500"
                                                x-show="rows.length > 1" title="削除">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <button type="button" @click="addRow()"
                                    class="flex flex-col items-center justify-center w-16 h-32 min-w-0 bg-white border border-dashed border-blue-400 rounded-lg text-blue-600 hover:bg-blue-200 focus:outline-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span class="text-xs">追加</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">保存</button>
                    <a href="{{ route('races.show', $race) }}" class="text-sm text-gray-600 hover:underline">キャンセル</a>
                </div>
            </form>
        </section>

        {{-- 払戻の整形（追加行にも効く：イベント委譲） --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('payout-form');
                if (!form) return;

                // blurはバブリングしないので capture=true
                form.addEventListener('blur', function(e) {
                    const input = e.target;
                    if (!input || input.tagName !== 'INPUT') return;
                    if (!input.name || !input.name.endsWith('[payout_per_100]')) return;

                    let v = input.value;
                    if (v === '') return;

                    v = String(v).replace(/[^0-9]/g, '');
                    if (!v) return;

                    const num = Number(v);
                    if (Number.isNaN(num)) return;

                    if (num <= 9) {
                        input.value = String(num) + '00';
                    } else if (num <= 99) {
                        input.value = String(num) + '0';
                    } else {
                        const str = String(num);
                        input.value = (str.slice(-1) !== '0') ? (str + '0') : str;
                    }
                }, true);
            });
        </script>

    </div>
</x-app-layout>
