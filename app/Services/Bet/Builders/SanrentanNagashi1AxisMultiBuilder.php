<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class SanrentanNagashi1AxisMultiBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $max = $this->maxHorse($race);

        return array_merge(parent::rules($race), [
            'axis' => ['required', 'integer', "between:1,{$max}"],
            'opponents' => ['required', 'array', 'min:2'],
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
            'opponents.min' => '相手を2頭以上選んでください。',

            'opponents.*.between' => '存在しない馬番が含まれています。',
            'opponents.*.distinct' => '同じ馬番が重複しています。',
        ]);
    }

    /**
     * 三連単 1頭軸流し（マルチ）
     * 軸（1頭）+ 相手（2頭以上）
     * 相手2頭の順序あり（b!=c）に対し
     * 軸が 1着/2着/3着 の3通りを展開
     *
     * 点数：3 * m * (m-1)
     */
    public function build(array $validated, Race $race): array
    {
        $axis = trim((string) $validated['axis']);
        $opp = $this->normalizeHorseList($validated['opponents']);
        $amount = (int) $validated['amount'];

        // 相手から軸を除外（UI側でも除外してるけど保険）
        $opp = array_values(array_filter($opp, fn($v) => $v !== $axis));
        $m = count($opp);
        if ($m < 2) {
            return [];
        }

        $keys = [];

        // (b,c) は順序あり
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $m; $j++) {
                if ($i === $j) continue;

                $b = $opp[$i];
                $c = $opp[$j];

                $keys[] = $this->ordered3($axis, $b, $c);
                $keys[] = $this->ordered3($b, $axis, $c);
                $keys[] = $this->ordered3($b, $c, $axis);
            }
        }

        return $this->itemsFromKeys('sanrentan', $keys, $amount);
    }
}
