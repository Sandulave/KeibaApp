<x-app-layout title="成績">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-8">
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">成績ランキング</h1>
            <p class="mt-1 text-sm text-gray-500">
                @if (($viewMode ?? 'user') === 'race')
                    レース別のユーザーランキング（配布金額・投資・回収・回収率・ボーナスPT・収支）を表示しています。
                @else
                    ユーザー別の投資・回収・回収率・ボーナスPT・現在残高を表示しています。
                @endif
            </p>
        </div>

        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('stats.index', ['view' => 'user', 'role' => $roleFilter]) }}"
                class="rounded-full px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium transition
                {{ ($viewMode ?? 'user') === 'user' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                ユーザー別
            </a>
            <a href="{{ route('stats.index', ['view' => 'race', 'role' => $roleFilter, 'race_id' => $selectedRaceId ?: null]) }}"
                class="rounded-full px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium transition
                {{ ($viewMode ?? 'user') === 'race' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                レース別
            </a>
        </div>

        @if (($viewMode ?? 'user') === 'race')
            <form method="GET" action="{{ route('stats.index') }}" class="mb-6 flex flex-wrap items-center gap-2">
                <input type="hidden" name="view" value="race">
                <input type="hidden" name="role" value="{{ $roleFilter }}">
                <label for="race_id" class="text-sm text-gray-700">対象レース</label>
                <select id="race_id" name="race_id" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                    @forelse(($raceOptions ?? collect()) as $raceOption)
                        <option value="{{ $raceOption->id }}" @selected((int) ($selectedRaceId ?? 0) === (int) $raceOption->id)>
                            {{ $raceOption->race_date }} / {{ $raceOption->name }}
                        </option>
                    @empty
                        <option value="">レースデータがありません</option>
                    @endforelse
                </select>
            </form>
            @if (!empty($selectedRace))
                <p class="mb-4 text-sm text-gray-600">
                    対象: {{ $selectedRace->race_date }} / {{ $selectedRace->name }}
                </p>
            @endif
        @endif

        <div class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('stats.index', ['view' => $viewMode ?? 'user', 'role' => 'all', 'race_id' => ($viewMode ?? 'user') === 'race' ? $selectedRaceId : null]) }}"
                class="rounded-full px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium transition
                {{ $roleFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                全体
            </a>
            <a href="{{ route('stats.index', ['view' => $viewMode ?? 'user', 'role' => 'streamer', 'race_id' => ($viewMode ?? 'user') === 'race' ? $selectedRaceId : null]) }}"
                class="rounded-full px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium transition
                {{ $roleFilter === 'streamer' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                配信者
            </a>
            <a href="{{ route('stats.index', ['view' => $viewMode ?? 'user', 'role' => 'viewer', 'race_id' => ($viewMode ?? 'user') === 'race' ? $selectedRaceId : null]) }}"
                class="rounded-full px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium transition
                {{ $roleFilter === 'viewer' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50' }}">
                視聴者
            </a>
        </div>

        @php
            $isRaceMode = ($viewMode ?? 'user') === 'race';
            $sortableHeaders = $isRaceMode
                ? [
                    'display_name' => 'ユーザー',
                    'allowance_amount' => '配布金額',
                    'total_stake' => '投資額',
                    'total_return' => '回収額',
                    'roi_percent' => '回収率',
                    'bonus_points' => 'ボーナスPT',
                    'total_amount' => '収支',
                ]
                : [
                    'display_name' => 'ユーザー',
                    'total_stake' => '投資額',
                    'total_return' => '回収額',
                    'roi_percent' => '回収率',
                    'bonus_points' => 'ボーナスPT',
                    'total_amount' => '現在残高',
                ];
        @endphp

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-[900px] sm:min-w-[980px] w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">順位</th>
                        @foreach($sortableHeaders as $key => $label)
                            @php
                                $isActive = ($sortKey ?? 'total_amount') === $key;
                                $currentDir = $sortDir ?? 'desc';
                                $defaultDir = $key === 'display_name' ? 'asc' : 'desc';
                                $nextDir = $isActive ? ($currentDir === 'desc' ? 'asc' : 'desc') : $defaultDir;
                                $arrow = $isActive ? ($currentDir === 'desc' ? '▼' : '▲') : '';
                            @endphp
                            <th class="px-3 sm:px-6 py-3 {{ $key === 'display_name' ? 'text-left' : 'text-right' }} text-xs font-medium uppercase tracking-wider">
                                <a href="{{ route('stats.index', ['view' => $viewMode ?? 'user', 'role' => $roleFilter, 'race_id' => ($viewMode ?? 'user') === 'race' ? $selectedRaceId : null, 'sort' => $key, 'dir' => $nextDir]) }}"
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
                            $isMe = auth()->check() && ((int) auth()->id() === (int) $row->user_id);
                            $roleRowClass = $isMe
                                ? 'bg-green-50 hover:bg-green-100'
                                : match ($row->audience_role_label) {
                                    '配信者' => 'bg-rose-50 hover:bg-rose-100',
                                    '視聴者' => 'bg-sky-50 hover:bg-sky-100',
                                    default => 'hover:bg-blue-50',
                                };
                        @endphp
                        <tr class="cursor-pointer transition-colors duration-150 {{ $roleRowClass }}"
                            onclick="window.location='{{ ($viewMode ?? 'user') === 'race' && !empty($selectedRaceId) ? route('stats.users.race-bets', [$row->user_id, $selectedRaceId]) : route('stats.users.show', $row->user_id) }}'">
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $rankByUserId[(int)$row->user_id] ?? '-' }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $row->display_name }}
                                <span class="ml-2 text-xs text-gray-500">
                                    ({{ $row->audience_role_label }})
                                </span>
                            </td>
                            @if ($isRaceMode)
                                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format((int) ($row->allowance_amount ?? 0)) }}円</td>
                            @endif
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-700 text-right">{{ number_format((int)$row->total_stake) }}円</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format((int)$row->total_return) }}円</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-right">
                                @if($row->roi_percent !== null)
                                    <span class="{{ $row->roi_percent >= 100 ? 'text-green-700 font-semibold' : 'text-gray-700' }}">
                                        {{ number_format((float)$row->roi_percent, 2) }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-right text-gray-700">{{ number_format((int)$row->bonus_points) }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900">{{ number_format((int)$row->total_amount) }}円</td>
                            <td class="px-1 py-3 sm:py-4 whitespace-nowrap text-center">
                                <a href="{{ ($viewMode ?? 'user') === 'race' && !empty($selectedRaceId) ? route('stats.users.race-bets', [$row->user_id, $selectedRaceId]) : route('stats.users.show', $row->user_id) }}"
                                   class="inline-flex items-center rounded bg-blue-600 px-1.5 py-0.5 text-[10px] font-semibold text-white hover:bg-blue-700"
                                   onclick="event.stopPropagation();">
                                    {{ ($viewMode ?? 'user') === 'race' ? '馬券詳細' : '詳細' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isRaceMode ? 9 : 8 }}" class="px-6 py-8 text-center text-sm text-gray-500">
                                {{ ($viewMode ?? 'user') === 'race' ? 'このレースの購入データがありません。' : 'まだ購入データがありません。' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
