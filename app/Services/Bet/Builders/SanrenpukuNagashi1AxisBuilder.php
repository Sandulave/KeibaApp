<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class SanrenpukuNagashi1AxisBuilder extends AbstractBetBuilder
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
     * 三連複 1頭軸流し
     * 軸1頭 + 相手（2頭以上）から相手2頭の組み合わせ（C(m,2)）
     * 三連複は順不同なので "1-2-3"（昇順固定）
     *
     * 点数：C(m,2) = m*(m-1)/2
     */
    public function build(array $validated, Race $race): array
    {
        $axis = trim((string) $validated['axis']);
        $opp = $this->normalizeHorseList($validated['opponents']);
        $amount = (int) $validated['amount'];

        // 相手から軸を除外（保険）
        $opp = array_values(array_filter($opp, fn($v) => $v !== $axis));
        $m = count($opp);
        if ($m < 2) {
            return [];
        }

        $keys = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = $i + 1; $j < $m; $j++) {
                $keys[] = $this->unordered3($axis, $opp[$i], $opp[$j]);
            }
        }

        return $this->itemsFromKeys('sanrenpuku', $keys, $amount);
    }
}
