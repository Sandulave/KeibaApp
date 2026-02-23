<x-app-layout title="レース選択">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight">レース選択</h1>
            <p class="mt-1 text-sm text-gray-500">
                購入対象レース：{{ count($races) }}件
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded bg-green-100 p-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">名前</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">開催日</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">コース</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">結果・配当</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($races as $race)
                        <tr class="group cursor-pointer hover:bg-blue-50 transition-colors duration-150"
                            onclick="window.location='{{ route('bet.types', $race) }}'">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $race->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $race->race_date }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $race->course }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if (($race->payouts_count ?? 0) > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        登録済み
                                    </span>
                                @else
                                    <span class="text-gray-400">未登録</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                レースがありません（管理者でレース登録してください）
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
