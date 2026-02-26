<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class SanrentanBoxBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $max = $this->maxHorse($race);

        return array_merge(parent::rules($race), [
            // 3頭以上。頭数上限はレース頭数に合わせる
            'horses' => ['required', 'array', 'min:3', "max:{$max}"],
            'horses.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'horses.required' => '馬番を選んでください。',
            'horses.array' => '馬番の形式が不正です。',
            'horses.min' => '3頭以上選んでください。',
            'horses.max' => '選択できる頭数が多すぎます。',
            'horses.*.between' => '存在しない馬番が含まれています。',
            'horses.*.distinct' => '同じ馬番が重複しています。',
        ]);
    }

    /**
     * 三連単ボックス：選んだ馬から 3頭順列（P(n,3)）を作る
     * selection_key は "1>2>3"
     */
    public function build(array $validated, Race $race): array
    {
        $horses = $this->normalizeHorseList($validated['horses']);
        $amount = (int) $validated['amount'];

        $keys = [];
        $n = count($horses);

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($j === $i) continue;

                for ($k = 0; $k < $n; $k++) {
                    if ($k === $i || $k === $j) continue;

                    $keys[] = $this->ordered3($horses[$i], $horses[$j], $horses[$k]);
                }
            }
        }

        return $this->itemsFromKeys('sanrentan', $keys, $amount);
    }
}
