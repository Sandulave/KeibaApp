<x-app-layout :title="$displayName . ' の成績'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="{{ route('stats.index') }}" class="text-sm text-blue-600 hover:underline">← 成績ランキングに戻る</a>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ $displayName }} の成績</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $audienceRoleLabel }}</p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded bg-green-100 p-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded bg-red-100 p-3 text-red-800 text-sm">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
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
            <div class="rounded-lg bg-white p-4 ring-1 ring-gray-200">
                <div class="text-xs text-gray-500">ボーナスPt / 繰越金 / 回収額</div>
                <div id="summaryCombined" class="mt-1 text-xl font-semibold">
                    {{ number_format($bonusPoints + $carryOverAmount + $totalReturn) }}円
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">レース別成績</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[1200px] w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th
                                class="px-3 py-3 align-middle text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                開催日</th>
                            <th
                                class="px-3 py-3 align-middle text-left text-xs font-medium text-gray-600 uppercase tracking-wider">
                                レース</th>
                            <th
                                class="px-3 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                投資額</th>
                            <th
                                class="px-3 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                回収額</th>
                            <th
                                class="px-3 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                回収率</th>
                            <th
                                class="px-3 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                ボーナスPt</th>
                            <th
                                class="px-3 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                繰越金</th>
                            <th
                                class="px-3 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider">
                                合計</th>
                            <th
                                class="px-3 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                ボーナスPt</th>
                            <th
                                class="px-3 py-3 align-middle text-right text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                繰越金</th>
                            <th
                                class="px-3 py-3 align-middle text-center text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                保存</th>
                            <th
                                class="px-3 py-3 align-middle text-center text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                削除</th>
                            <th
                                class="px-3 py-3 align-middle text-center text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                                馬券詳細</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($raceRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-4 align-middle whitespace-nowrap text-sm text-gray-700">
                                    {{ $row->race_date }}</td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap text-sm text-gray-900">
                                    {{ $row->race_name }}</td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap text-sm text-right text-gray-700">
                                    {{ number_format((int) $row->total_stake) }}円</td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap text-sm text-right text-gray-700">
                                    {{ number_format((int) $row->total_return) }}円</td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap text-sm text-right text-gray-700">
                                    {{ $row->roi_percent !== null ? number_format((float) $row->roi_percent, 2) . '%' : '-' }}
                                </td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap text-sm text-right text-gray-700" data-display="bonus">
                                    {{ number_format((int) $row->bonus_points) }}</td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap text-sm text-right text-gray-700" data-display="carry">
                                    {{ number_format((int) $row->carry_over_amount) }}</td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap text-sm text-right text-gray-700" data-display="sum">
                                    {{ number_format((int) $row->total_return + (int) $row->bonus_points + (int) $row->carry_over_amount) }}円
                                </td>
                                @if ($canEditAdjustments)
                                    @php($formId = 'adjustment-form-' . $row->race_id)
                                    <td class="px-3 py-3 align-middle whitespace-nowrap text-sm text-right">
                                        <form id="{{ $formId }}" method="POST"
                                            action="{{ route('stats.users.adjustments.update', $user) }}" class="js-adjustment-form">
                                            @csrf
                                            <input type="hidden" name="race_id" value="{{ $row->race_id }}">
                                            <input type="number" name="bonus_points" min="0" max="1000000"
                                                value="{{ old('race_id') == $row->race_id ? old('bonus_points') : (int) $row->bonus_points }}"
                                                class="w-24 rounded border-gray-300 text-sm text-right" placeholder="ボーナス">
                                        </form>
                                    </td>
                                    <td class="px-3 py-3 align-middle whitespace-nowrap text-sm text-right">
                                        <input type="number" name="carry_over_amount" min="0" max="1000000"
                                            form="{{ $formId }}"
                                            value="{{ old('race_id') == $row->race_id ? old('carry_over_amount') : (int) $row->carry_over_amount }}"
                                            class="w-24 rounded border-gray-300 text-sm text-right" placeholder="繰越金">
                                    </td>
                                    <td class="px-3 py-3 align-middle whitespace-nowrap text-sm text-center">
                                        <button type="submit" form="{{ $formId }}"
                                            class="js-adjustment-save rounded bg-blue-600 px-2 py-1 text-xs text-white hover:bg-blue-700"
                                            >
                                            保存
                                        </button>
                                    </td>
                                @else
                                    <td class="px-3 py-3 align-middle whitespace-nowrap text-sm text-center text-gray-400">-</td>
                                    <td class="px-3 py-3 align-middle whitespace-nowrap text-sm text-center text-gray-400">-</td>
                                    <td class="px-3 py-3 align-middle whitespace-nowrap text-sm text-center text-gray-400">-</td>
                                @endif
                                <td class="px-3 py-3 align-middle whitespace-nowrap text-sm text-center">
                                    @if ($canEditAdjustments)
                                        <form method="POST" action="{{ route('stats.users.adjustments.destroy', $user) }}"
                                            onsubmit="return confirm('このレースの馬券・ボーナスPT・繰越金を削除します。よろしいですか？');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="race_id" value="{{ $row->race_id }}">
                                            <button type="submit"
                                                class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700">
                                                削除
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-middle whitespace-nowrap text-sm text-center">
                                    <a href="{{ route('stats.users.race-bets', [$user, $row->race_id]) }}"
                                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-800 hover:bg-gray-200">
                                        馬券詳細
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-6 py-8 text-center text-sm text-gray-500">
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
        <div id="adjustmentToast"
            class="fixed right-4 top-20 z-50 hidden rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-lg">
            保存しました
        </div>
        <script>
            (() => {
                const toNumber = (v) => {
                    const n = parseInt(String(v ?? '').replace(/,/g, ''), 10);
                    return Number.isNaN(n) ? 0 : n;
                };
                const formatYen = (v) => `${v.toLocaleString('ja-JP')}円`;
                const formatNum = (v) => v.toLocaleString('ja-JP');
                const summaryReturnEl = document.getElementById('summaryTotalReturn');
                const summaryCombinedEl = document.getElementById('summaryCombined');
                const toast = document.getElementById('adjustmentToast');
                let toastTimer = null;

                const showToast = (message) => {
                    if (!toast) return;
                    toast.textContent = message;
                    toast.classList.remove('hidden');
                    if (toastTimer) clearTimeout(toastTimer);
                    toastTimer = setTimeout(() => toast.classList.add('hidden'), 1800);
                };

                const refreshSummary = () => {
                    const returnTotal = Array.from(document.querySelectorAll('tbody tr td:nth-child(4)'))
                        .reduce((sum, el) => sum + toNumber(el.textContent), 0);
                    const bonusTotal = Array.from(document.querySelectorAll('tbody tr [data-display="bonus"]'))
                        .reduce((sum, el) => sum + toNumber(el.textContent), 0);
                    const carryTotal = Array.from(document.querySelectorAll('tbody tr [data-display="carry"]'))
                        .reduce((sum, el) => sum + toNumber(el.textContent), 0);

                    if (summaryReturnEl) summaryReturnEl.textContent = formatYen(returnTotal);
                    if (summaryCombinedEl) summaryCombinedEl.textContent = formatYen(returnTotal + bonusTotal + carryTotal);
                };

                document.querySelectorAll('.js-adjustment-save').forEach((button) => {
                    button.addEventListener('click', async (e) => {
                        e.preventDefault();

                        const formId = button.getAttribute('form');
                        const form = formId ? document.getElementById(formId) : null;
                        const row = button.closest('tr');
                        const bonusInput = form?.querySelector('input[name="bonus_points"]');
                        const carryInput = row?.querySelector(`input[name="carry_over_amount"][form="${formId}"]`);
                        const returnCell = row?.querySelector('td:nth-child(4)');
                        const bonusCell = row?.querySelector('[data-display="bonus"]');
                        const carryCell = row?.querySelector('[data-display="carry"]');
                        const sumCell = row?.querySelector('[data-display="sum"]');

                        if (!form || !row || !bonusInput || !carryInput || !returnCell || !bonusCell || !carryCell || !sumCell) {
                            return;
                        }

                        const formData = new FormData(form);
                        formData.set('carry_over_amount', carryInput.value ?? '0');
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

                            const totalReturn = toNumber(returnCell.textContent);
                            const bonus = toNumber(bonusInput.value);
                            const carry = toNumber(carryInput.value);

                            bonusCell.textContent = formatNum(bonus);
                            carryCell.textContent = formatNum(carry);
                            sumCell.textContent = formatYen(totalReturn + bonus + carry);
                            refreshSummary();
                            showToast('保存しました');
                        } catch (err) {
                            alert('保存に失敗しました。入力値または権限を確認してください。');
                        } finally {
                            button.disabled = false;
                        }
                    });
                });
            })();
        </script>
    @endif
</x-app-layout>
