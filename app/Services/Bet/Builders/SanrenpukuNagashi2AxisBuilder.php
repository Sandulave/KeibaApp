<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class SanrenpukuNagashi2AxisBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $max = $this->maxHorse($race);

        return array_merge(parent::rules($race), [
            'axis1' => ['required', 'integer', "between:1,{$max}"],
            'axis2' => ['required', 'integer', "between:1,{$max}", 'different:axis1'],

            'opponents' => ['required', 'array', 'min:1'],
            'opponents.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'axis1.required' => '軸1を選んでください。',
            'axis2.required' => '軸2を選んでください。',
            'axis1.between' => '存在しない馬番です。',
            'axis2.between' => '存在しない馬番です。',
            'axis2.different' => '軸1と軸2は別の馬を選んでください。',

            'opponents.required' => '相手を選んでください。',
            'opponents.array' => '相手の形式が不正です。',
            'opponents.min' => '相手を1頭以上選んでください。',

            'opponents.*.between' => '存在しない馬番が含まれています。',
            'opponents.*.distinct' => '同じ馬番が重複しています。',
        ]);
    }

    /**
     * 三連複 2頭軸流し（順不同）
     * 軸2頭 + 相手（1頭以上） → "a-b-c"（昇順固定）
     *
     * 点数：m（相手頭数）
     */
    public function build(array $validated, Race $race): array
    {
        $a1 = trim((string) $validated['axis1']);
        $a2 = trim((string) $validated['axis2']);
        $opp = $this->normalizeHorseList($validated['opponents']);
        $amount = (int) $validated['amount'];

        // 相手から軸2頭を除外（保険）
        $opp = array_values(array_filter($opp, fn($v) => $v !== $a1 && $v !== $a2));
        if (count($opp) < 1) {
            return [];
        }

        $keys = [];
        foreach ($opp as $c) {
            $keys[] = $this->unordered3($a1, $a2, $c);
        }

        return $this->itemsFromKeys('sanrenpuku', $keys, $amount);
    }
}
