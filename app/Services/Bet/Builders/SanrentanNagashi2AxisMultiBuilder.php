<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class SanrentanNagashi2AxisMultiBuilder extends AbstractBetBuilder
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
     * 三連単 2頭軸流し（マルチ）
     *
     * ここでは「軸2頭が 1-2着（入替あり）」＋「相手が3着」を展開します。
     * （＝一般的な “2頭軸マルチ” の最小形。）
     *
     * 点数：2 * m （m=相手頭数）
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
            // 3頭（a1,a2,c）の全順列（3! = 6通り）
            $keys[] = $this->ordered3($a1, $a2, $c);
            $keys[] = $this->ordered3($a1, $c, $a2);
            $keys[] = $this->ordered3($a2, $a1, $c);
            $keys[] = $this->ordered3($a2, $c, $a1);
            $keys[] = $this->ordered3($c, $a1, $a2);
            $keys[] = $this->ordered3($c, $a2, $a1);
        }

        return $this->itemsFromKeys('sanrentan', $keys, $amount);
    }
}
