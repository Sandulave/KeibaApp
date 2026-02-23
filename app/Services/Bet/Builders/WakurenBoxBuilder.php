<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class WakurenBoxBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $maxFrame = min(8, $this->maxHorse($race));

        return array_merge(parent::rules($race), [
            'frames' => ['required', 'array', 'min:2', 'max:8'],
            'frames.*' => ['required', 'integer', "between:1,{$maxFrame}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'frames.required' => '枠番を選んでください。',
            'frames.array' => '枠番の形式が不正です。',
            'frames.min' => '2枠以上選んでください。',
            'frames.max' => '選択できる枠数が多すぎます。',
            'frames.*.between' => '存在しない枠番が含まれています。',
            'frames.*.distinct' => '同じ枠番が重複しています。',
        ]);
    }

    /**
     * 枠連ボックス：順序なし2枠（C(n,2)）
     * selection_key は "1-2"（昇順固定）
     */
    public function build(array $validated, Race $race): array
    {
        $frames = $this->normalizeHorseList($validated['frames']); // 文字列正規化に流用
        $amount = (int) $validated['amount'];

        // normalizeHorseList は数値昇順の string を返すので OK
        $keys = [];
        $n = count($frames);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $keys[] = $this->unordered2($frames[$i], $frames[$j]);
            }
        }

        return $this->itemsFromKeys('wakuren', $keys, $amount);
    }
}
