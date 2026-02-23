<x-app-layout title="買い方選択（{{ $race->name }}）">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex justify-between">
            <a href="{{ route('bet.types', $race) }}" class="text-sm text-blue-600 underline">
                ← 券種選択に戻る
            </a>
        </div>
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
            <div class="text-sm text-gray-600">レース</div>
            <div class="font-semibold">{{ $race->name }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6 space-y-4">
            <h2 class="text-lg font-semibold">
                券種：{{ $typeLabel }}
            </h2>

            @if (empty($modes))
                <div class="text-sm text-gray-500">この券種はまだ準備中です</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($modes as $modeKey => $mode)
                        <a href="{{ route('bet.build.mode', [$race, $betType, $modeKey]) }}"
                            class="block rounded-lg border border-gray-300 p-4 text-center hover:bg-gray-50">
                            <div class="font-semibold">{{ $mode['label'] ?? $modeKey }}</div>
                            @if (!empty($mode['description']))
                                <div class="text-sm text-gray-500 mt-1">
                                    {{ $mode['description'] }}
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>



    </div>
</x-app-layout>
