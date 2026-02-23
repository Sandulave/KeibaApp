<x-app-layout :title="$displayName . ' / ' . $race->name . ' の馬券詳細'">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <a href="{{ route('stats.users.show', $user) }}" class="text-sm text-blue-600 hover:underline">← 個別成績に戻る</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight">{{ $displayName }} / {{ $race->name }} の馬券詳細</h1>
            <p class="mt-1 text-sm text-gray-500">開催日: {{ $race->race_date }}</p>
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
            <table class="w-full table-auto">
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
</x-app-layout>
