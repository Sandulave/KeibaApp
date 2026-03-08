<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WakurenFormationBuilder extends AbstractBetBuilder
{
    public function validate(Request $request, Race $race): array
    {
        $validator = Validator::make($request->all(), $this->rules($race), $this->messages());

        $validator->after(function ($validator) use ($race) {
            $data = $validator->getData();
            $first = collect($data['first'] ?? [])
                ->map(fn ($v) => (int) $v)
                ->all();
            $second = collect($data['second'] ?? [])
                ->map(fn ($v) => (int) $v)
                ->all();

            $invalidSameFrames = collect(array_intersect($first, $second))
                ->filter(fn ($frame) => !$this->canUseSameFramePair($race, (int) $frame))
                ->values()
                ->all();

            if ($invalidSameFrames !== []) {
                $labels = collect($invalidSameFrames)
                    ->map(fn ($frame) => (string) $frame)
                    ->all();
                $pairLabels = collect($labels)->map(fn ($v) => "{$v}-{$v}")->implode(' / ');
                $validator->errors()->add('second', "1頭のみの枠では同一枠は購入できません（{$pairLabels}）。");
            }
        });

        return $validator->validate();
    }

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
                if ((int) $a === (int) $b && !$this->canUseSameFramePair($race, (int) $a)) {
                    continue;
                }
                $key = $this->unordered2($a, $b);
                $set[$key] = true;
            }
        }

        return $this->itemsFromKeys('wakuren', array_keys($set), $amount);
    }
}
