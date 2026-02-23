<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class WideNagashi1AxisBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $max = $this->maxHorse($race);

        return array_merge(parent::rules($race), [
            'axis' => ['required', 'integer', "between:1,{$max}"],
            'opponents' => ['required', 'array', 'min:1'],
            'opponents.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'axis.required' => '軸馬を選んでください。',
            'axis.between' => '存在しない馬番です。',

            'opponents.required' => '相手を選んでください。',
            'opponents.array' => '相手の形式が不正です。',
            'opponents.min' => '相手を1頭以上選んでください。',

            'opponents.*.between' => '存在しない馬番が含まれています。',
            'opponents.*.distinct' => '同じ馬番が重複しています。',
        ]);
    }

    /**
     * ワイド 1頭軸流し：軸 + 相手（順不同）
     * selection_key は "1-2"（昇順固定）
     *
     * 点数：m（相手頭数）
     */
    public function build(array $validated, Race $race): array
    {
        $axis = trim((string) $validated['axis']);
        $opp = $this->normalizeHorseList($validated['opponents']);
        $amount = (int) $validated['amount'];

        // 相手から軸を除外（保険）
        $opp = array_values(array_filter($opp, fn($v) => $v !== $axis));
        if (count($opp) < 1) {
            return [];
        }

        $keys = [];
        foreach ($opp as $b) {
            $keys[] = $this->unordered2($axis, $b);
        }

        return $this->itemsFromKeys('wide', $keys, $amount);
    }
}
