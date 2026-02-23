<x-app-layout title="成績">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight">成績ランキング</h1>
            <p class="mt-1 text-sm text-gray-500">
                ユーザー別の投資・回収・回収率・ボーナスPT・繰越金・合計を表示しています。
            </p>
        </div>

        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('stats.index', ['role' => 'all']) }}"
                class="rounded-full px-4 py-2 text-sm font-medium transition
                {{ $roleFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                全体
            </a>
            <a href="{{ route('stats.index', ['role' => 'streamer']) }}"
                class="rounded-full px-4 py-2 text-sm font-medium transition
                {{ $roleFilter === 'streamer' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                配信者
            </a>
            <a href="{{ route('stats.index', ['role' => 'viewer']) }}"
                class="rounded-full px-4 py-2 text-sm font-medium transition
                {{ $roleFilter === 'viewer' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                視聴者
            </a>
        </div>

        @php
            $sortableHeaders = [
                'display_name' => 'ユーザー',
                'total_stake' => '投資額',
                'total_return' => '回収額',
                'roi_percent' => '回収率',
                'bonus_points' => 'ボーナスPT',
                'carry_over_amount' => '繰越金',
                'total_amount' => '合計',
            ];
        @endphp

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-[980px] w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">順位</th>
                        @foreach($sortableHeaders as $key => $label)
                            @php
                                $isActive = ($sortKey ?? 'total_amount') === $key;
                                $currentDir = $sortDir ?? 'desc';
                                $defaultDir = $key === 'display_name' ? 'asc' : 'desc';
                                $nextDir = $isActive ? ($currentDir === 'desc' ? 'asc' : 'desc') : $defaultDir;
                                $arrow = $isActive ? ($currentDir === 'desc' ? '▼' : '▲') : '';
                            @endphp
                            <th class="px-6 py-3 {{ $key === 'display_name' ? 'text-left' : 'text-right' }} text-xs font-medium uppercase tracking-wider">
                                <a href="{{ route('stats.index', ['role' => $roleFilter, 'sort' => $key, 'dir' => $nextDir]) }}"
                                   class="inline-flex items-center gap-1 transition {{ $isActive ? 'text-blue-700 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                                    <span>{{ $label }}</span>
                                    <span aria-hidden="true" class="text-[10px] leading-none">{{ $arrow }}</span>
                                </a>
                            </th>
                        @endforeach
                        <th class="px-1 py-3 w-14 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                            詳細
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($rows as $row)
                        @php
                            $roleRowClass = match ($row->audience_role_label) {
                                '配信者' => 'bg-rose-50 hover:bg-rose-100',
                                '視聴者' => 'bg-sky-50 hover:bg-sky-100',
                                default => 'hover:bg-blue-50',
                            };
                        @endphp
                        <tr class="cursor-pointer transition-colors duration-150 {{ $roleRowClass }}"
                            onclick="window.location='{{ route('stats.users.show', $row->user_id) }}'">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $rankByUserId[(int)$row->user_id] ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $row->display_name }}
                                <span class="ml-2 text-xs text-gray-500">
                                    ({{ $row->audience_role_label }})
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">{{ number_format((int)$row->total_stake) }}円</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format((int)$row->total_return) }}円</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                @if($row->roi_percent !== null)
                                    <span class="{{ $row->roi_percent >= 100 ? 'text-green-700 font-semibold' : 'text-gray-700' }}">
                                        {{ number_format((float)$row->roi_percent, 2) }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format((int)$row->bonus_points) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format((int)$row->carry_over_amount) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900">{{ number_format((int)$row->total_amount) }}円</td>
                            <td class="px-1 py-4 whitespace-nowrap text-center">
                                <a href="{{ route('stats.users.show', $row->user_id) }}"
                                   class="inline-flex items-center rounded bg-blue-600 px-1.5 py-0.5 text-[10px] font-semibold text-white hover:bg-blue-700"
                                   onclick="event.stopPropagation();">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-8 text-center text-sm text-gray-500">
                                まだ購入データがありません。
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
