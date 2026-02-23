<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class WakurenNagashi1AxisBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $maxFrame = min(8, $this->maxHorse($race));

        return array_merge(parent::rules($race), [
            'axis' => ['required', 'integer', "between:1,{$maxFrame}"],
            'opponents' => ['required', 'array', 'min:1'],
            'opponents.*' => ['required', 'integer', "between:1,{$maxFrame}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'axis.required' => '軸枠を選んでください。',
            'axis.between' => '存在しない枠番です。',

            'opponents.required' => '相手枠を選んでください。',
            'opponents.array' => '相手枠の形式が不正です。',
            'opponents.min' => '相手枠を1枠以上選んでください。',

            'opponents.*.between' => '存在しない枠番が含まれています。',
            'opponents.*.distinct' => '同じ枠番が重複しています。',
        ]);
    }

    /**
     * 枠連 1枠軸流し（順不同）
     * selection_key は "1-2"（昇順固定）
     */
    public function build(array $validated, Race $race): array
    {
        $axis = trim((string) $validated['axis']);
        $opp = $this->normalizeHorseList($validated['opponents']);
        $amount = (int) $validated['amount'];

        $keys = [];
        foreach ($opp as $b) {
            $keys[] = $this->unordered2($axis, $b);
        }

        return $this->itemsFromKeys('wakuren', $keys, $amount);
    }
}
