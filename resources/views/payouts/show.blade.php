<x-app-layout :title="$race->name">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-base">

        {{-- パンくず/戻る --}}
        <div class="mb-6">
            <a href="{{ route('races.index') }}"
                class="inline-flex items-center gap-2 text-base text-blue-600 hover:text-blue-700 hover:underline">
                <span aria-hidden="true">←</span>
                レース一覧に戻る
            </a>
        </div>

        {{-- ヘッダー --}}
        <div class="mb-8">
            <h1 class="text-4xl font-bold tracking-tight">
                {{ $race->name }}
            </h1>
            <p class="mt-3 text-base text-gray-600">
                レース詳細
            </p>
        </div>

        {{-- 基本情報カード --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6 mb-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <div class="text-sm font-medium text-gray-500">開催日</div>
                    <div class="mt-2 text-xl font-semibold text-gray-900">
                        {{ $race->race_date }}
                    </div>
                </div>

                <div>
                    <div class="text-sm font-medium text-gray-500">コース</div>
                    <div class="mt-2 text-xl font-semibold text-gray-900">
                        {{ $race->course }}
                    </div>
                </div>
            </div>
        </div>

        <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6 mb-10">
            <h2 class="text-2xl font-semibold mb-4">購入集計</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">
                    <div class="text-sm text-gray-500">総投資額</div>
                    <div class="mt-1 text-xl font-bold">{{ number_format($totalStake) }}円</div>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">
                    <div class="text-sm text-gray-500">総回収額</div>
                    <div class="mt-1 text-xl font-bold">{{ number_format($totalReturn) }}円</div>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">
                    <div class="text-sm text-gray-500">回収率</div>
                    <div class="mt-1 text-xl font-bold">
                        {{ $overallRoi !== null ? number_format($overallRoi, 2) . '%' : '-' }}
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="py-2 pr-4">購入日時</th>
                            <th class="py-2 pr-4">ユーザー</th>
                            <th class="py-2 pr-4">投資</th>
                            <th class="py-2 pr-4">回収</th>
                            <th class="py-2 pr-4">回収率</th>
                            <th class="py-2 pr-4">的中数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bets as $bet)
                            <tr class="border-b last:border-b-0">
                                <td class="py-2 pr-4 whitespace-nowrap">{{ optional($bet->bought_at)->format('Y-m-d H:i') }}</td>
                                <td class="py-2 pr-4">{{ $bet->user->name ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ number_format((int)$bet->stake_amount) }}円</td>
                                <td class="py-2 pr-4">{{ number_format((int)$bet->return_amount) }}円</td>
                                <td class="py-2 pr-4">
                                    {{ $bet->roi_percent !== null ? number_format((float)$bet->roi_percent, 2) . '%' : '-' }}
                                </td>
                                <td class="py-2 pr-4">{{ (int)$bet->hit_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-gray-500">購入データはまだありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 結果・配当 --}}
        <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold">結果・配当</h2>
                <div class="flex items-center gap-3">
                    <a href="{{ route('races.settlement.edit', $race) }}" class="text-base text-blue-600 hover:underline">
                        結果・配当を登録/編集
                    </a>
                </div>
            </div>

            @php($betLabels = config('domain.bet.type_labels', []))

            @forelse($payoutsSorted as $payout)
                <div class="flex items-center justify-between py-4 border-b last:border-b-0">
                    <div class="min-w-0">
                        <div class="text-base font-semibold text-gray-900">
                            {{ $betLabels[$payout->bet_type] ?? $payout->bet_type }}
                        </div>
                        <div class="text-sm text-gray-600 truncate mt-1">
                            {{ $payout->selection_key }}
                        </div>
                    </div>
                    <div class="text-lg font-bold text-gray-900 whitespace-nowrap">
                        {{ number_format($payout->payout_per_100) }}円
                    </div>
                </div>
            @empty
                <p class="text-gray-600 text-base">
                    まだ結果・配当が登録されていません。
                </p>
            @endforelse
        </section>
    </div>
</x-app-layout>
