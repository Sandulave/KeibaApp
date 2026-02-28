<x-app-layout title="レース選択">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-8">
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">レース選択</h1>
            <p class="mt-1 text-sm text-gray-500">
                購入対象レース：{{ count($races) }}件
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded bg-green-100 p-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded bg-red-100 p-3 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] table-auto">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">名前</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">開催日</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">コース</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">結果・配当</th>
                        <th class="px-3 sm:px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">購入・詳細</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($races as $race)
                        @php
                            $isHorseCountInvalid = (int) ($race->horse_count ?? 0) <= 0;
                            $isPurchaseDisabled = $race->is_betting_closed || $isHorseCountInvalid;
                            $isDetailDisabled = $isHorseCountInvalid;
                        @endphp
                        <tr class="transition-colors duration-150 {{ $isHorseCountInvalid ? 'bg-gray-50' : 'hover:bg-gray-50' }}">
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm font-medium {{ $isHorseCountInvalid ? 'text-gray-500' : 'text-gray-900' }}">{{ $race->name }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm {{ $isHorseCountInvalid ? 'text-gray-500' : 'text-gray-600' }}">{{ $race->race_date }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm {{ $isHorseCountInvalid ? 'text-gray-500' : 'text-gray-600' }}">{{ $race->course }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm {{ $isHorseCountInvalid ? 'text-gray-500' : 'text-gray-600' }}">
                                @if (($race->payouts_count ?? 0) > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        登録済み
                                    </span>
                                @else
                                    <span class="text-gray-400">未登録</span>
                                @endif
                                @if ($race->is_betting_closed)
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        投票受付終了
                                    </span>
                                @endif
                                @if ($isHorseCountInvalid)
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">
                                        頭数未設定
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-4 py-3 sm:py-4 whitespace-nowrap text-sm {{ $isHorseCountInvalid ? 'text-gray-500' : 'text-gray-600' }}">
                                <div class="flex flex-nowrap items-center gap-2">
                                    @if ($isPurchaseDisabled)
                                        <span
                                            class="inline-flex shrink-0 whitespace-nowrap items-center rounded-md bg-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-500 cursor-not-allowed">
                                            購入不可
                                        </span>
                                    @else
                                        <a href="{{ route('bet.challenge.select', $race) }}"
                                            class="inline-flex shrink-0 whitespace-nowrap items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition">
                                            購入へ
                                        </a>
                                    @endif
                                    @if ($isDetailDisabled)
                                        <span class="inline-flex shrink-0 whitespace-nowrap items-center rounded-md border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-500 cursor-not-allowed">
                                            購入馬券詳細
                                        </span>
                                    @else
                                        <a href="{{ route('stats.users.race-bets', [auth()->id(), $race->id]) }}"
                                            class="inline-flex shrink-0 whitespace-nowrap items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 transition">
                                            購入馬券詳細
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                レースがありません（管理者でレース登録してください）
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
