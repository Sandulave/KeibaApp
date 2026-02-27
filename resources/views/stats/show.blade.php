<x-app-layout :title="$displayName . ' の成績'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="{{ route('stats.index') }}" class="text-sm text-blue-600 hover:underline">← 成績ランキングに戻る</a>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ $displayName }} の成績</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $audienceRoleLabel }}</p>
        </div>

        <div id="adjustmentNotice"
            class="mb-6 hidden rounded bg-green-100 px-4 py-4 text-lg font-semibold text-green-800 sm:text-xl">
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded bg-red-100 p-3 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <div class="rounded-lg bg-white p-4 ring-1 ring-gray-200">
                <div class="text-xs text-gray-500">総投資額</div>
                <div class="mt-1 text-xl font-semibold">{{ number_format($totalStake) }}円</div>
            </div>
            <div class="rounded-lg bg-white p-4 ring-1 ring-gray-200">
                <div class="text-xs text-gray-500">総回収額</div>
                <div id="summaryTotalReturn" class="mt-1 text-xl font-semibold">{{ number_format($totalReturn) }}円</div>
            </div>
            <div class="rounded-lg bg-white p-4 ring-1 ring-gray-200">
                <div class="text-xs text-gray-500">回収率</div>
                <div class="mt-1 text-xl font-semibold">
                    {{ $overallRoi !== null ? number_format($overallRoi, 2) . '%' : '-' }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">レース別成績</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full table-fixed text-sm">
                    <colgroup>
                        <col class="w-[8%]">
                        <col class="w-[11%]">
                        <col class="w-[8%]">
                        <col class="w-[7%]">
                        <col class="w-[7%]">
                        <col class="w-[7%]">
                        <col class="w-[8%]">
                        <col class="w-[9%]">
                        <col class="w-[10%]">
                        <col class="w-[7%]">
                        <col class="w-[7%]">
                        <col class="w-[9%]">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th
                                class="px-2 py-3 align-middle text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                開催日</th>
                            <th
                                class="px-2 py-3 align-middle text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                レース</th>
                            <th
                                class="px-2 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                配布金額</th>
                            <th
                                class="px-2 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                投資額</th>
                            <th
                                class="px-2 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                回収額</th>
                            <th
                                class="px-2 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                回収率</th>
                            <th
                                class="px-2 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                合計</th>
                            <th
                                class="px-2 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                ボーナスPt</th>
                            <th
                                class="px-2 py-3 align-middle text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                勝負レース</th>
                            <th
                                class="px-2 py-3 align-middle text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                保存</th>
                            <th
                                class="px-2 py-3 align-middle text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                削除</th>
                            <th
                                class="px-2 py-3 align-middle text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                馬券詳細</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($raceRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-4 align-middle text-gray-700 whitespace-nowrap">
                                    {{ $row->race_date }}</td>
                                <td class="px-2 py-4 align-middle text-gray-900 truncate" title="{{ $row->race_name }}">
                                    {{ $row->race_name }}</td>
                                <td class="px-2 py-4 align-middle text-right text-gray-700">
                                    @php
                                        $allowanceAmount = match ($row->challenge_choice ?? null) {
                                            'challenge' => 30000,
                                            'normal' => 10000,
                                            default => 0,
                                        };
                                    @endphp
                                    <span data-display="allowance">{{ number_format($allowanceAmount) }}</span>円
                                </td>
                                <td class="px-2 py-4 align-middle text-right text-gray-700" data-display="stake">
                                    {{ number_format((int) $row->total_stake) }}円</td>
                                <td class="px-2 py-4 align-middle text-right text-gray-700" data-display="return">
                                    {{ number_format((int) $row->total_return) }}円</td>
                                <td class="px-2 py-4 align-middle text-right text-gray-700">
                                    {{ $row->roi_percent !== null ? number_format((float) $row->roi_percent, 2) . '%' : '-' }}
                                </td>
                                <td class="px-2 py-4 align-middle text-right text-gray-700" data-display="sum">
                                    {{ number_format($allowanceAmount - (int) $row->total_stake + (int) $row->total_return + (int) $row->bonus_points) }}円
                                </td>
                                @if ($canEditAdjustments)
                                    @php
                                        $formId = 'adjustment-form-' . $row->race_id;
                                    @endphp
                                    <td class="px-2 py-3 align-middle text-right text-sm">
                                        <form id="{{ $formId }}" method="POST"
                                            action="{{ route('stats.users.adjustments.update', $user) }}" class="js-adjustment-form">
                                            @csrf
                                            <input type="hidden" name="race_id" value="{{ $row->race_id }}">
                                            <input type="number" name="bonus_points" min="{{ -1 * $adjustmentMax }}" max="{{ $adjustmentMax }}"
                                                value="{{ old('race_id') == $row->race_id ? old('bonus_points') : (int) $row->bonus_points }}"
                                                class="w-full max-w-[5rem] rounded border-gray-300 text-sm text-right" placeholder="ボーナス">
                                        </form>
                                    </td>
                                    <td class="px-2 py-3 align-middle text-center text-sm">
                                        @if (($row->challenge_choice ?? null) === 'challenge')
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">勝負</span>
                                        @elseif (($row->challenge_choice ?? null) === 'normal')
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">通常</span>
                                        @else
                                            <span class="text-gray-400">未選択</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 align-middle text-center text-sm">
                                        <button type="submit" form="{{ $formId }}"
                                            class="js-adjustment-save rounded bg-blue-600 px-2 py-1 text-xs text-white hover:bg-blue-700"
                                            >
                                            保存
                                        </button>
                                    </td>
                                @else
                                    <td class="px-2 py-3 align-middle text-right text-sm text-gray-700">
                                        {{ number_format((int) $row->bonus_points) }}
                                    </td>
                                    <td class="px-2 py-3 align-middle text-center text-sm text-gray-700">
                                        @if (($row->challenge_choice ?? null) === 'challenge')
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">勝負</span>
                                        @elseif (($row->challenge_choice ?? null) === 'normal')
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">通常</span>
                                        @else
                                            <span class="text-gray-400">未選択</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 align-middle text-center text-sm text-gray-400">-</td>
                                @endif
                                <td class="px-2 py-3 align-middle text-center text-sm">
                                    @if ($canEditAdjustments)
                                        @if ((bool) $row->is_betting_closed)
                                            <button type="button"
                                                class="rounded bg-gray-300 px-2 py-1 text-xs text-white cursor-not-allowed"
                                                disabled
                                                title="投票終了レースは削除できません">
                                                削除
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('stats.users.adjustments.destroy', $user) }}"
                                                onsubmit="return confirm('このレースの馬券・ボーナスPTを削除します。よろしいですか？');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="race_id" value="{{ $row->race_id }}">
                                                <button type="submit"
                                                    class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700">
                                                    削除
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-2 py-3 align-middle text-center text-sm">
                                    <a href="{{ route('stats.users.race-bets', [$user, $row->race_id]) }}"
                                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-800 hover:bg-gray-200">
                                        馬券詳細
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-6 py-8 text-center text-sm text-gray-500">
                                    まだ購入データがありません。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @if ($canEditAdjustments)
        <script>
            (() => {
                const toNumber = (v) => {
                    const n = parseInt(String(v ?? '').replace(/,/g, ''), 10);
                    return Number.isNaN(n) ? 0 : n;
                };
                const formatYen = (v) => `${v.toLocaleString('ja-JP')}円`;
                const formatNum = (v) => v.toLocaleString('ja-JP');
                const summaryReturnEl = document.getElementById('summaryTotalReturn');
                const notice = document.getElementById('adjustmentNotice');
                const initialSuccessMessage = @json(session('success'));
                const currentBalanceEl = document.getElementById('js-current-balance-amount');
                const setNoticeVariant = (variant) => {
                    if (!notice) return;
                    notice.classList.remove('bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                    if (variant === 'danger') {
                        notice.classList.add('bg-red-100', 'text-red-800');
                        return;
                    }
                    notice.classList.add('bg-green-100', 'text-green-800');
                };

                const showNotice = (message, variant = 'success') => {
                    if (!notice) return;
                    setNoticeVariant(variant);
                    notice.textContent = message;
                    notice.classList.remove('hidden');
                };

                const refreshSummary = () => {
                    const returnTotal = Array.from(document.querySelectorAll('tbody tr td:nth-child(5)'))
                        .reduce((sum, el) => sum + toNumber(el.textContent), 0);

                    if (summaryReturnEl) summaryReturnEl.textContent = formatYen(returnTotal);
                };

                document.querySelectorAll('.js-adjustment-save').forEach((button) => {
                    button.addEventListener('click', async (e) => {
                        e.preventDefault();

                        const formId = button.getAttribute('form');
                        const form = formId ? document.getElementById(formId) : null;
                        const row = button.closest('tr');
                        const bonusInput = form?.querySelector('input[name="bonus_points"]');
                        const returnCell = row?.querySelector('td:nth-child(5)');
                        const sumCell = row?.querySelector('[data-display="sum"]');

                        if (!form || !row || !bonusInput || !returnCell || !sumCell) {
                            return;
                        }

                        const formData = new FormData(form);
                        button.disabled = true;

                        try {
                            const res = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: formData,
                                credentials: 'same-origin',
                            });

                            if (!res.ok) {
                                throw new Error('保存に失敗しました');
                            }
                            const payload = await res.json();

                            const totalReturn = toNumber(returnCell.textContent);
                            const stakeCell = row?.querySelector('[data-display="stake"]');
                            const allowanceCell = row?.querySelector('[data-display="allowance"]');
                            const bonus = toNumber(bonusInput.value);
                            const totalStake = toNumber(stakeCell?.textContent ?? '0');
                            const allowanceAmount = toNumber(allowanceCell?.textContent ?? '0');

                            sumCell.textContent = formatYen(allowanceAmount - totalStake + totalReturn + bonus);
                            if (currentBalanceEl && payload && typeof payload.current_balance === 'number') {
                                currentBalanceEl.textContent = payload.current_balance.toLocaleString('ja-JP');
                            }
                            refreshSummary();
                            showNotice('保存しました');
                        } catch (err) {
                            alert('保存に失敗しました。入力値または権限を確認してください。');
                        } finally {
                            button.disabled = false;
                        }
                    });
                });

                if (initialSuccessMessage) {
                    const variant = initialSuccessMessage.includes('削除') ? 'danger' : 'success';
                    showNotice(initialSuccessMessage, variant);
                }
            })();
        </script>
    @elseif (session('success'))
        <script>
            (() => {
                const notice = document.getElementById('adjustmentNotice');
                if (!notice) return;
                const message = @json(session('success'));
                notice.classList.remove('bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
                notice.classList.add(message.includes('削除') ? 'bg-red-100' : 'bg-green-100');
                notice.classList.add(message.includes('削除') ? 'text-red-800' : 'text-green-800');
                notice.textContent = message;
                notice.classList.remove('hidden');
            })();
        </script>
    @endif
</x-app-layout>
