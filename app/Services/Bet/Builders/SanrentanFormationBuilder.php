<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class SanrentanFormationBuilder extends AbstractBetBuilder
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
            'first.required' => '1着候補を選んでください。',
            'second.required' => '2着候補を選んでください。',
            'third.required' => '3着候補を選んでください。',

            'first.*.between' => '存在しない馬番が含まれています。',
            'second.*.between' => '存在しない馬番が含まれています。',
            'third.*.between' => '存在しない馬番が含まれています。',
        ]);
    }

    /**
     * 三連単フォーメーション
     * 1着候補 × 2着候補 × 3着候補
     * 同一馬は除外
     */
    public function build(array $validated, Race $race): array
    {
        $A = $this->normalizeHorseList($validated['first']);
        $B = $this->normalizeHorseList($validated['second']);
        $C = $this->normalizeHorseList($validated['third']);
        $amount = (int) $validated['amount'];

        $keys = [];

        foreach ($A as $a) {
            foreach ($B as $b) {
                if ($a === $b) continue;

                foreach ($C as $c) {
                    if ($c === $a || $c === $b) continue;

                    $keys[] = $this->ordered3($a, $b, $c);
                }
            }
        }

        return $this->itemsFromKeys('sanrentan', $keys, $amount);
    }
}
