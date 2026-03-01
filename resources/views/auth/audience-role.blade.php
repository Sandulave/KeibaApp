<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            参加区分の選択
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-700">
                    初回ログインのため、あなたの参加区分を選択してください。後から変更したい場合は運営に連絡してください。
                </p>

                <form method="POST" action="{{ route('audience-role.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <label class="flex items-start gap-3 rounded-md border border-rose-200 bg-rose-50 p-4">
                        <input
                            type="radio"
                            name="audience_role"
                            value="streamer"
                            @checked(old('audience_role', $currentAudienceRole) === 'streamer')
                            class="mt-1"
                        >
                        <span>
                            <span class="block font-medium text-rose-900">配信者</span>
                            <span class="block text-sm text-rose-800">配信を行う側の参加者はこちらを選択してください。</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-md border border-sky-200 bg-sky-50 p-4">
                        <input
                            type="radio"
                            name="audience_role"
                            value="viewer"
                            @checked(old('audience_role', $currentAudienceRole) === 'viewer')
                            class="mt-1"
                        >
                        <span>
                            <span class="block font-medium text-sky-900">視聴者</span>
                            <span class="block text-sm text-sky-800">配信を視聴して参加する側はこちらを選択してください。</span>
                        </span>
                    </label>

                    <x-input-error :messages="$errors->get('audience_role')" class="mt-2" />

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                        >
                            この内容で進む
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
