<x-app-layout :title="$displayName . ' / ' . $race->name . ' の馬券詳細'">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
        <div>
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('stats.users.show', $user) }}" class="text-sm text-blue-600 hover:underline">← 個別成績に戻る</a>
                <a href="{{ route('bet.races') }}" class="text-sm text-blue-600 hover:underline">← レース選択に戻る</a>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ $displayName }} / {{ $race->name }} の馬券詳細</h1>
            <p class="mt-1 text-sm text-gray-500">開催日: {{ $race->race_date }}</p>
        </div>

        <div class="rounded-lg bg-white p-3 ring-1 ring-gray-200">
            <h2 class="text-xs font-semibold text-gray-900">レース結果</h2>
            <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                @foreach ([1, 2, 3] as $rank)
                    <div class="rounded-md bg-gray-50 px-2 py-1.5">
                        <div class="text-[11px] text-gray-500">{{ $rank }}着</div>
                        <div class="mt-0.5 font-semibold text-gray-900">
                            {{ !empty($resultByRank[$rank]) ? implode(', ', $resultByRank[$rank]) : '-' }}
                        </div>
                    </div>
                @endforeach
                <div class="rounded-md bg-gray-50 px-2 py-1.5">
                    <div class="text-[11px] text-gray-500">取消</div>
                    <div class="mt-0.5 font-semibold text-gray-900">
                        {{ !empty($withdrawalHorses) ? implode(', ', $withdrawalHorses) : '-' }}
                    </div>
                </div>
            </div>
            @if (empty($resultByRank[1]) && empty($resultByRank[2]) && empty($resultByRank[3]) && empty($withdrawalHorses))
                <p class="mt-2 text-xs text-gray-500">このレースの結果はまだ登録されていません。</p>
            @endif
        </div>

        <div class="rounded-lg bg-white p-3 ring-1 ring-gray-200">
            <h2 class="text-xs font-semibold text-gray-900">券種ごとの配当</h2>
            @if ($payoutsByBetType->isNotEmpty())
                @php
                    $betTypeColors = [
                        'tansho' => 'bg-[#5a67b3]',
                        'fukusho' => 'bg-[#b75b4f]',
                        'wakuren' => 'bg-[#78a95f]',
                        'umaren' => 'bg-[#7a5a98]',
                        'wide' => 'bg-[#6a9eab]',
                        'umatan' => 'bg-[#c9a247]',
                        'sanrenpuku' => 'bg-[#4f8cb8]',
                        'sanrentan' => 'bg-[#c9843f]',
                    ];
                    $displayOrder = ['tansho', 'fukusho', 'wakuren', 'umaren', 'wide', 'umatan', 'sanrenpuku', 'sanrentan'];
                    $groupsByType = $payoutsByBetType->mapWithKeys(function ($rows, $betType) use ($betTypeLabels) {
                        return [
                            $betType => [
                                'betType' => $betType,
                                'label' => $betTypeLabels[$betType] ?? $betType,
                                'rows' => $rows->values(),
                            ],
                        ];
                    });
                    $orderedGroups = collect();
                    foreach ($displayOrder as $betType) {
                        if ($groupsByType->has($betType)) {
                            $orderedGroups->push($groupsByType[$betType]);
                        }
                    }
                    foreach ($groupsByType as $betType => $group) {
                        if (!in_array($betType, $displayOrder, true)) {
                            $orderedGroups->push($group);
                        }
                    }
                    $columns = [
                        $orderedGroups->slice(0, 4)->values(),
                        $orderedGroups->slice(4)->values(),
                    ];
                @endphp

                <div class="mt-2 grid grid-cols-1 lg:grid-cols-2 gap-3">
                    @foreach ($columns as $column)
                        @if ($column->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="min-w-[360px] w-full text-xs border border-gray-300 border-collapse bg-white">
                                    <tbody>
                                        @foreach ($column as $group)
                                            @foreach ($group['rows'] as $index => $row)
                                                <tr class="border-b border-gray-300 last:border-b-0">
                                                    @if ($index === 0)
                                                        <td rowspan="{{ $group['rows']->count() }}"
                                                            class="w-20 px-2 py-2 text-center text-white font-bold {{ $betTypeColors[$group['betType']] ?? 'bg-gray-500' }}">
                                                            {{ $group['label'] }}
                                                        </td>
                                                    @endif
                                                    <td class="px-3 py-2 border-l border-gray-300 text-center font-semibold text-gray-800">
                                                        {{ $row->selection_key }}
                                                    </td>
                                                    <td class="px-3 py-2 border-l border-gray-300 text-right font-bold text-gray-800">
                                                        {{ number_format((int) $row->payout_per_100) }}円
                                                    </td>
                                                    <td class="px-2 py-2 border-l border-gray-300 text-right text-gray-700 whitespace-nowrap">
                                                        {{ $row->popularity ? $row->popularity . '人気' : '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-xs text-gray-500">このレースの配当はまだ登録されていません。</p>
            @endif
        </div>

        @php
            $snapshotBets = $bets->filter(fn($bet) => filled($bet->snapshot_text))->values();
        @endphp
        <div class="rounded-lg bg-white p-3 ring-1 ring-gray-200">
            <h2 class="text-xs font-semibold text-gray-900">購入内容（入力ベース）</h2>
            @if ($snapshotBets->isNotEmpty())
                <div class="mt-2 space-y-2">
                    @foreach ($snapshotBets as $bet)
                        <div class="rounded-md border border-gray-200 bg-gray-50 p-2">
                            <div class="text-[11px] text-gray-500">
                                購入日時: {{ optional($bet->bought_at)->format('Y-m-d H:i:s') ?? '-' }}
                            </div>
                            <div class="mt-1 whitespace-pre-line font-mono text-xs leading-5 text-gray-900">{{ $bet->snapshot_text }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-xs text-gray-500">表示可能な購入入力データがありません（旧データ）。</p>
            @endif
        </div>

        @php
            $itemTotal = $bets->sum(fn($bet) => $bet->items->count());
            $stakeTotal = $bets->sum(fn($bet) => (int) $bet->stake_amount);
            $returnTotal = $bets->sum(fn($bet) => (int) $bet->return_amount);
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg bg-white p-4 ring-1 ring-gray-200">
                <div class="text-xs text-gray-500">買い目数</div>
                <div class="mt-1 text-xl font-semibold">{{ number_format($itemTotal) }}</div>
            </div>
            <div class="rounded-lg bg-white p-4 ring-1 ring-gray-200">
                <div class="text-xs text-gray-500">投資額</div>
                <div class="mt-1 text-xl font-semibold">{{ number_format($stakeTotal) }}円</div>
            </div>
            <div class="rounded-lg bg-white p-4 ring-1 ring-gray-200">
                <div class="text-xs text-gray-500">回収額</div>
                <div class="mt-1 text-xl font-semibold">{{ number_format($returnTotal) }}円</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">購入日時</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">券種</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">買い目</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">金額</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">払戻</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">的中</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($bets as $bet)
                        @foreach ($bet->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ optional($bet->bought_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $betTypeLabels[$item->bet_type] ?? $item->bet_type }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900">{{ $item->selection_key }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format((int) $item->amount) }}円</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format((int) ($item->return_amount ?? 0)) }}円</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center">
                                    @if ($item->is_hit)
                                        <span class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">的中</span>
                                    @else
                                        <span class="rounded bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                このレースの購入馬券はありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
