<?php

namespace App\Http\Requests;

use App\Enums\BetType;
use App\Models\Race;
use Illuminate\Foundation\Http\FormRequest;

class RaceSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $race = $this->route('race');
        $horseMax = $race instanceof Race
            ? (int) $race->horse_count
            : (int) config('domain.bet.default_horse_count', 18);
        $payoutMin = (int) config('domain.bet.payout.min', 100);
        $payoutStep = (int) config('domain.bet.payout.step', 10);
        $popularityMin = (int) config('domain.bet.popularity.min', 1);

        return [
            'ranks' => ['required', 'array'],
            'ranks.1' => ['array'],
            'ranks.2' => ['array'],
            'ranks.3' => ['array'],
            'ranks.*.*' => ['integer', 'min:1', "max:{$horseMax}"],
            'withdrawals' => ['nullable', 'array'],
            'withdrawals.*' => ['integer', 'min:1', "max:{$horseMax}"],
            'payouts' => ['required', 'array'],
            'payouts.*' => ['array'],
            'payouts.*.*' => ['array'],
            'payouts.*.*.selection_key' => ['nullable', 'string', 'regex:/^[0-9]+([->,][0-9]+)*$/'],
            'payouts.*.*.payout_per_100' => ['nullable', 'integer', "min:{$payoutMin}", "multiple_of:{$payoutStep}"],
            'payouts.*.*.popularity' => ['nullable', 'integer', "min:{$popularityMin}"],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $ranks = $this->input('ranks', []);
            $all = collect($ranks[1] ?? [])
                ->merge($ranks[2] ?? [])
                ->merge($ranks[3] ?? []);
            if ($all->count() !== $all->unique()->count()) {
                $v->errors()->add('ranks', '同じ馬番が複数の着順に登録されています。');
            }
            foreach ([1,2,3] as $rank) {
                if (!empty($ranks[$rank]) && count($ranks[$rank]) !== count(array_unique($ranks[$rank]))) {
                    $v->errors()->add('ranks.'.$rank, "{$rank}着内で重複があります。");
                }
            }

            $payouts = $this->input('payouts', []);
            foreach ($payouts as $betType => $rows) {
                if (!BetType::tryFrom((string)$betType)) {
                    $v->errors()->add('payouts', "未対応の券種です: {$betType}");
                    continue;
                }

                if (!is_array($rows)) {
                    continue;
                }

                foreach ($rows as $idx => $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $selectionKey = trim((string)($row['selection_key'] ?? ''));
                    $payoutRaw = $row['payout_per_100'] ?? null;
                    $payoutStr = trim((string)$payoutRaw);

                    $isEmptyRow = ($selectionKey === '' && $payoutStr === '');
                    if ($isEmptyRow) {
                        continue;
                    }

                    if ($selectionKey === '' && $payoutStr !== '') {
                        $v->errors()->add("payouts.{$betType}.{$idx}.selection_key", '当たり目を入力してください。');
                    }
                    if ($selectionKey !== '' && $payoutStr === '') {
                        $v->errors()->add("payouts.{$betType}.{$idx}.payout_per_100", '払戻金を入力してください。');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'ranks.required' => '着順データがありません。',
            'ranks.array' => '着順データの形式が不正です。',
            'ranks.1.array' => '1着の入力形式が不正です。',
            'ranks.2.array' => '2着の入力形式が不正です。',
            'ranks.3.array' => '3着の入力形式が不正です。',
            'ranks.*.*.integer' => '着順の馬番は数値で入力してください。',
            'ranks.*.*.min' => '馬番は1以上で入力してください。',
            'ranks.*.*.max' => '馬番がレース頭数を超えています。',

            'withdrawals.array' => '取消馬データの形式が不正です。',
            'withdrawals.*.integer' => '取消馬の馬番は数値で入力してください。',
            'withdrawals.*.min' => '取消馬の馬番は1以上で入力してください。',
            'withdrawals.*.max' => '取消馬の馬番がレース頭数を超えています。',

            'payouts.required' => '払戻データがありません。',
            'payouts.array' => '払戻データの形式が不正です。',
            'payouts.*.array' => '券種ごとの払戻データ形式が不正です。',
            'payouts.*.*.array' => '払戻行データの形式が不正です。',
            'payouts.*.*.selection_key.string' => '当たり目は文字列で入力してください。',
            'payouts.*.*.selection_key.regex' => '当たり目の形式が不正です（例: 1-2 / 1>2>3）。',
            'payouts.*.*.payout_per_100.integer' => '払戻金は数値で入力してください。',
            'payouts.*.*.payout_per_100.min' => '払戻金は100円以上で入力してください。',
            'payouts.*.*.payout_per_100.multiple_of' => '払戻金は10円単位で入力してください。',
            'payouts.*.*.popularity.integer' => '人気は数値で入力してください。',
            'payouts.*.*.popularity.min' => '人気は1以上で入力してください。',
        ];
    }
}
