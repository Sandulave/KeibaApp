<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class TanshoSingleBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $max = $this->maxHorse($race);

        return array_merge(parent::rules($race), [
            // UIは horse[]（複数選択）なので array で受ける
            'horse' => ['required', 'array', 'min:1'],
            'horse.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'horse.required' => '馬番を選んでください。',
            'horse.array' => '馬番の形式が不正です。',
            'horse.min' => '馬番を1頭以上選んでください。',
            'horse.*.required' => '馬番を選んでください。',
            // これが無いと validation.integer がそのまま出る環境があるので明示
            'horse.*.integer' => '馬番は数値で指定してください。',
            'horse.*.between' => '存在しない馬番です。',
            'horse.*.distinct' => '同じ馬番が重複しています。',
        ]);
    }

    /**
     * 単勝：複数選択可（horse[]）
     * selection_key は "7"（そのまま）
     *
     * 点数：選択馬数
     */
    public function build(array $validated, Race $race): array
    {
        $horses = collect($validated['horse'] ?? [])
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->sort(fn($a, $b) => ((int)$a) <=> ((int)$b))
            ->values()
            ->all();

        $amount = (int) $validated['amount'];

        return array_map(fn($horse) => [
            'bet_type' => 'tansho',
            'selection_key' => $horse,
            'amount' => $amount,
        ], $horses);
    }
}
