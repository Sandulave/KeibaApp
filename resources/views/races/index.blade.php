<x-app-layout title="レース一覧">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-6 sm:py-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-100 p-3 text-green-800 ring-1 ring-green-200">
                {{ session('success') }}
            </div>
        @endif

        {{-- ヘッダー --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">レース一覧</h1>
                <p class="mt-1 text-sm text-gray-500">
                    登録済みレース：{{ count($races) }}件
                </p>
            </div>
            <a href="{{ route('races.create') }}"
               class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm sm:text-base text-white font-medium hover:bg-blue-700 transition-colors">
                + 新規作成
            </a>
        </div>

        {{-- テーブル --}}
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-[700px] w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">名前</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">開催日</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">コース</th>
                        <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Result</th>
                        <th class="px-3 sm:px-6 py-3 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($races as $race)
                        <tr class="group cursor-pointer hover:bg-blue-50 transition-colors duration-150" onclick="window.location='{{ route('races.show', $race) }}'">
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm font-medium text-gray-900 cursor-pointer">{{ $race->name }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 cursor-pointer">{{ $race->race_date }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 cursor-pointer">{{ $race->course }}</td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-600 cursor-pointer">
                                @if($race->payouts && count($race->payouts))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        登録済み
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-right text-sm font-medium space-x-2 cursor-auto" onclick="event.stopPropagation()">
                                <a href="{{ route('races.edit', $race) }}" class="text-blue-600 hover:text-blue-900">編集</a>
                                <form action="{{ route('races.destroy', $race) }}" method="POST" style="display:inline;" onsubmit="event.stopPropagation(); return confirm('削除しますか？')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                レースはまだ登録されていません。<br>
                                <a href="{{ route('races.create') }}" class="text-blue-600 hover:underline">新しいレースを追加する</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>
