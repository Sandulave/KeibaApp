<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;
use Illuminate\Http\Request;

abstract class AbstractBetBuilder implements BetBuilderInterface
{
    /**
     * 1点あたり金額などの共通バリデーション
     */
    public function rules(Race $race): array
    {
        $amountMin = (int) config('domain.bet.amount.min', 100);
        $amountMax = (int) config('domain.bet.amount.max', 1_000_000);
        $amountStep = (int) config('domain.bet.amount.step', 100);

        return [
            // UIと整合：100円単位
            'amount' => ['required', 'integer', "min:{$amountMin}", "max:{$amountMax}", "multiple_of:{$amountStep}"],
        ];
    }

    /**
     * バリデーションメッセージ（各Builderで必要なら上書き）
     */
    public function messages(): array
    {
        return [
            'amount.required' => '金額を入力してください。',
            'amount.integer' => '金額は数値で入力してください。',
            'amount.min' => '金額は100円以上で入力してください。',
            'amount.max' => '金額が大きすぎます。',
            'amount.multiple_of' => '金額は100円単位で入力してください。',
        ];
    }

    /**
     * validate() の標準実装：rules/messages を使う
     * 各Builderは rules()/messages() を実装 or 上書きする
     */
    public function validate(Request $request, Race $race): array
    {
        return $request->validate(
            $this->rules($race),
            $this->messages()
        );
    }

    /**
     * races.horse_count が無い/未設定の保険込み
     */
    protected function maxHorse(Race $race): int
    {
        return (int)($race->horse_count ?? config('domain.bet.default_horse_count', 18));
    }

    /**
     * @return array<int, string> 文字列・ユニーク・昇順
     */
    protected function normalizeHorseList(array $values): array
    {
        $uniq = collect($values)
            ->map(fn($v) => trim((string)$v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        usort($uniq, fn($a, $b) => (int)$a <=> (int)$b);
        return array_values($uniq);
    }

    /**
     * 順序ありキー（例：三連単 1>2>3 / 馬単 1>2）
     */
    protected function ordered2(string $a, string $b): string
    {
        return $a . '>' . $b;
    }

    protected function ordered3(string $a, string $b, string $c): string
    {
        return $a . '>' . $b . '>' . $c;
    }

    /**
     * 順序なしキー（例：馬連 1-2 / 三連複 1-2-3）
     */
    protected function unordered2(string $a, string $b): string
    {
        $x = (int)$a;
        $y = (int)$b;
        return ($x <= $y) ? ($a . '-' . $b) : ($b . '-' . $a);
    }

    protected function unordered3(string $a, string $b, string $c): string
    {
        $pairs = [
            (int)$a => $a,
            (int)$b => $b,
            (int)$c => $c,
        ];
        ksort($pairs);
        return implode('-', array_values($pairs));
    }

    /**
     * @param string $betType
     * @param array<int, string> $keys
     * @param int $amount
     * @return array<int, array{bet_type:string, selection_key:string, amount:int}>
     */
    protected function itemsFromKeys(string $betType, array $keys, int $amount): array
    {
        $items = [];
        foreach ($keys as $k) {
            $items[] = [
                'bet_type' => $betType,
                'selection_key' => $k,
                'amount' => $amount,
            ];
        }
        return $items;
    }

    /**
     * build() は各Builderで実装
     */
    abstract public function build(array $validated, Race $race): array;
}
