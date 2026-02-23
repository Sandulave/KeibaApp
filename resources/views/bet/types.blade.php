<x-app-layout title="券種選択（{{ $race->name }}）">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div>
            <a href="{{ route('bet.races') }}" class="text-sm text-blue-600 underline">
                ← レース選択に戻る
            </a>
        </div>
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-4">
            <div class="text-sm text-gray-600">レース</div>
            <div class="font-semibold">{{ $race->name }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 p-6 space-y-4">
            <h2 class="text-lg font-semibold">券種を選択してください</h2>

            @if (empty($types))
                <div class="text-sm text-gray-500">券種がまだ登録されていません</div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($types as $betTypeKey => $type)
                        <a href="{{ route('bet.modes', [$race, $betTypeKey]) }}"
                            class="block rounded-lg border border-gray-300 p-4 text-center hover:bg-gray-50">
                            <div class="font-semibold">
                                {{ $type['label'] ?? $betTypeKey }}
                            </div>
                            @if (!empty($type['description']))
                                <div class="text-sm text-gray-500 mt-1">
                                    {{ $type['description'] }}
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>



    </div>
</x-app-layout>
