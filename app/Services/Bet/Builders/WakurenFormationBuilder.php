<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class WakurenFormationBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $maxFrame = min(8, $this->maxHorse($race));

        return array_merge(parent::rules($race), [
            'first' => ['required', 'array', 'min:1'],
            'first.*' => ['required', 'integer', "between:1,{$maxFrame}", 'distinct'],

            'second' => ['required', 'array', 'min:1'],
            'second.*' => ['required', 'integer', "between:1,{$maxFrame}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'first.required' => '1列目（枠）を選んでください。',
            'second.required' => '2列目（枠）を選んでください。',
            'first.*.between' => '存在しない枠番が含まれています。',
            'second.*.between' => '存在しない枠番が含まれています。',
            'first.*.distinct' => '同じ枠番が重複しています。',
            'second.*.distinct' => '同じ枠番が重複しています。',
        ]);
    }

    /**
     * 枠連フォーメーション（順不同）
     * selection_key は "1-2"（昇順固定）
     */
    public function build(array $validated, Race $race): array
    {
        $A = $this->normalizeHorseList($validated['first']);  // 文字列化・昇順
        $B = $this->normalizeHorseList($validated['second']);
        $amount = (int) $validated['amount'];

        $set = [];
        foreach ($A as $a) {
            foreach ($B as $b) {
                $key = $this->unordered2($a, $b);
                $set[$key] = true;
            }
        }

        return $this->itemsFromKeys('wakuren', array_keys($set), $amount);
    }
}
