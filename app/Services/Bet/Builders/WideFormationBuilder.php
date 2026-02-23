<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class WideFormationBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $max = $this->maxHorse($race);

        return array_merge(parent::rules($race), [
            'first' => ['required', 'array', 'min:1'],
            'first.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],

            'second' => ['required', 'array', 'min:1'],
            'second.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'first.required' => '1列目を選んでください。',
            'second.required' => '2列目を選んでください。',
            'first.*.between' => '存在しない馬番が含まれています。',
            'second.*.between' => '存在しない馬番が含まれています。',
            'first.*.distinct' => '同じ馬番が重複しています。',
            'second.*.distinct' => '同じ馬番が重複しています。',
        ]);
    }

    /**
     * ワイドフォーメーション（順不同）
     * 1列目×2列目 からペア生成（同一馬は除外）
     * selection_key は "1-2"（昇順固定）
     */
    public function build(array $validated, Race $race): array
    {
        $A = $this->normalizeHorseList($validated['first']);
        $B = $this->normalizeHorseList($validated['second']);
        $amount = (int) $validated['amount'];

        $set = [];
        foreach ($A as $a) {
            foreach ($B as $b) {
                if ($a === $b) continue;
                $key = $this->unordered2($a, $b);
                $set[$key] = true;
            }
        }

        return $this->itemsFromKeys('wide', array_keys($set), $amount);
    }
}
