<x-app-layout title="代理入力">
    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">代理入力</h1>
                <p class="mt-1 text-sm text-gray-600">管理者が対象ユーザーの購入データと調整値を直接編集できます。</p>
            </div>

            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.proxy-entry.bet-ui.start') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    @csrf
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700">対象ユーザー</label>
                        <select id="user_id" name="user_id" class="mt-1 block w-full rounded border-gray-300 text-sm">
                            <option value="">選択してください</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((int) $selectedUserId === (int) $user->id)>
                                    {{ $user->display_name ?: $user->name }} (ID: {{ $user->id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="inline-flex items-center rounded bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                            読み込む
                        </button>
                    </div>
                </form>
            </div>

            @if ($selectedUser)
                <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-4 sm:p-6">
                    <h2 class="text-lg font-semibold text-gray-900">既存の馬券購入UIで代理入力</h2>
                    <p class="mt-1 text-xs text-gray-500">対象ユーザーを選んで開始すると、そのまま購入画面へ遷移します。</p>

                    @if ($proxyUser)
                        <div class="mt-3 rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                            代理購入モード中: {{ $proxyUser->display_name ?: $proxyUser->name }} (ID: {{ $proxyUser->id }})
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('admin.proxy-entry.bet-ui.start') }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                            <button type="submit" class="inline-flex items-center rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                このユーザーで購入UIを開始
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.proxy-entry.bet-ui.stop') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-300">
                                代理購入モード終了
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($selectedUser)
                <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl p-4 sm:p-6">
                    <h2 class="text-lg font-semibold text-gray-900">購入済みデータ</h2>
                    <p class="mt-1 text-xs text-gray-500">対象: {{ $selectedUser->display_name ?: $selectedUser->name }}（最新100件）</p>

                    <div class="mt-4 space-y-5">
                        @forelse ($bets as $bet)
                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                    <span>Bet ID: {{ $bet->id }}</span>
                                    <span>レース: {{ $bet->race?->race_date }} {{ $bet->race?->name }}</span>
                                    <span>購入日時: {{ optional($bet->bought_at)->format('Y-m-d H:i:s') ?? '-' }}</span>
                                    <span>投資額: {{ number_format((int) $bet->stake_amount) }}円</span>
                                    <span>回収額: {{ number_format((int) $bet->return_amount) }}円</span>
                                </div>

                                <div class="mt-3 overflow-x-auto">
                                    <table class="min-w-[860px] w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200">
                                                <th class="px-2 py-2 text-left">券種</th>
                                                <th class="px-2 py-2 text-left">買い目</th>
                                                <th class="px-2 py-2 text-right">金額</th>
                                                <th class="px-2 py-2 text-right">払戻</th>
                                                <th class="px-2 py-2 text-center">的中</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($bet->items as $item)
                                                <tr>
                                                    <td class="px-2 py-2">{{ $betTypeLabels[$item->bet_type] ?? $item->bet_type }}</td>
                                                    <td class="px-2 py-2 font-mono">{{ $item->selection_key }}</td>
                                                    <td class="px-2 py-2 text-right">{{ number_format((int) $item->amount) }}円</td>
                                                    <td class="px-2 py-2 text-right">{{ number_format((int) ($item->return_amount ?? 0)) }}円</td>
                                                    <td class="px-2 py-2 text-center">
                                                        @if ($item->is_hit)
                                                            <span class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">的中</span>
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">この条件の購入データはまだありません。</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
