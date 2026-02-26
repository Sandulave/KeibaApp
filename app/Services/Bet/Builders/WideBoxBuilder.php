<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class WideBoxBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $max = $this->maxHorse($race);

        return array_merge(parent::rules($race), [
            // ワイドボックス：2頭以上
            // 頭数上限はレース頭数に合わせる
            'horses' => ['required', 'array', 'min:2', "max:{$max}"],
            'horses.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'horses.required' => '馬番を選んでください。',
            'horses.array' => '馬番の形式が不正です。',
            'horses.min' => '2頭以上選んでください。',
            'horses.max' => '選択できる頭数が多すぎます。',
            'horses.*.between' => '存在しない馬番が含まれています。',
            'horses.*.distinct' => '同じ馬番が重複しています。',
        ]);
    }

    /**
     * ワイドボックス：順序なし2頭（C(n,2)）
     * selection_key は "1-2"（昇順固定）
     *
     * 点数：C(n,2)=n*(n-1)/2
     */
    public function build(array $validated, Race $race): array
    {
        $horses = $this->normalizeHorseList($validated['horses']);
        $amount = (int) $validated['amount'];

        $keys = [];
        $n = count($horses);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $keys[] = $this->unordered2($horses[$i], $horses[$j]);
            }
        }

        return $this->itemsFromKeys('wide', $keys, $amount);
    }
}
