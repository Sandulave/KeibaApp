<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">メンテナンス設定</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if (session('status') === 'maintenance-updated')
                    <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                        メンテナンス設定を更新しました。
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.maintenance.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="enabled" value="状態" />
                        <select id="enabled" name="enabled" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="0" @selected(! $enabled)>通常運用（メンテOFF）</option>
                            <option value="1" @selected($enabled)>メンテナンス中（メンテON）</option>
                        </select>
                        <x-input-error :messages="$errors->get('enabled')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="message" value="表示メッセージ（任意）" />
                        <x-text-input id="message" name="message" type="text" class="mt-1 block w-full" :value="old('message', $message)" placeholder="例: 本日22:00までメンテナンス予定です" />
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>保存する</x-primary-button>
                        <p class="text-sm text-gray-500">ONにすると管理者以外には503ページが表示されます。</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
