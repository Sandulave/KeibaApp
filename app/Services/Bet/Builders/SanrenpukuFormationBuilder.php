<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class SanrenpukuFormationBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $max = $this->maxHorse($race);

        return array_merge(parent::rules($race), [
            'first' => ['required', 'array', 'min:1'],
            'first.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],

            'second' => ['required', 'array', 'min:1'],
            'second.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],

            'third' => ['required', 'array', 'min:1'],
            'third.*' => ['required', 'integer', "between:1,{$max}", 'distinct'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'first.required' => '1列目を選んでください。',
            'second.required' => '2列目を選んでください。',
            'third.required' => '3列目を選んでください。',

            'first.*.between' => '存在しない馬番が含まれています。',
            'second.*.between' => '存在しない馬番が含まれています。',
            'third.*.between' => '存在しない馬番が含まれています。',

            'first.*.distinct' => '同じ馬番が重複しています。',
            'second.*.distinct' => '同じ馬番が重複しています。',
            'third.*.distinct' => '同じ馬番が重複しています。',
        ]);
    }

    /**
     * 三連複フォーメーション
     * 1列目×2列目×3列目 から3頭を作る（同一馬は除外）
     * 三連複は順不同なので selection_key は "1-2-3"（昇順固定）
     *
     * 例：A={1,2}, B={2,3}, C={3,4}
     * 生成：1-2-3 / 1-3-4 / 2-3-4 ...（重複はsetで消す）
     */
    public function build(array $validated, Race $race): array
    {
        $A = $this->normalizeHorseList($validated['first']);
        $B = $this->normalizeHorseList($validated['second']);
        $C = $this->normalizeHorseList($validated['third']);
        $amount = (int) $validated['amount'];

        $set = [];

        foreach ($A as $a) {
            foreach ($B as $b) {
                if ($a === $b) continue;

                foreach ($C as $c) {
                    if ($c === $a || $c === $b) continue;

                    $key = $this->unordered3($a, $b, $c);
                    $set[$key] = true; // 重複排除
                }
            }
        }

        return $this->itemsFromKeys('sanrenpuku', array_keys($set), $amount);
    }
}
