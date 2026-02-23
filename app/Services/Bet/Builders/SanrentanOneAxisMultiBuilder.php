<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;

class SanrentanOneAxisMultiBuilder extends AbstractBetBuilder
{
    public function rules(Race $race): array
    {
        $maxHorse = (int) ($race->horse_count ?? 18);

        return [
            'axis' => ['required', 'integer', "between:1,{$maxHorse}"],

            'opponents' => ['required', 'array', 'min:2', "max:{$maxHorse}"],
            'opponents.*' => [
                'required',
                'integer',
                "between:1,{$maxHorse}",
                'distinct:strict',
                // 相手に軸を混ぜない（differentは「別値である」ルール）
                'different:axis',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'axis.required' => '軸馬を1頭選んでください。',
            'opponents.required' => '相手を選んでください。',
            'opponents.min' => '相手は2頭以上選んでください。',
            'opponents.*.different' => '軸馬を相手に含めることはできません。',
        ];
    }

    public function build(array $validated, Race $race): array
    {
        $axis = trim((string) $validated['axis']);

        $opp = collect($validated['opponents'])
            ->map(fn($v) => trim((string)$v))
            ->filter()
            ->unique()
            ->reject(fn($v) => $v === $axis) // 保険
            ->values()
            ->all();

        $amount = (int) $validated['amount'];

        $m = count($opp);
        if ($m < 2) return [];

        $items = [];

        // (b,c) は順序あり
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $m; $j++) {
                if ($i === $j) continue;

                $b = $opp[$i];
                $c = $opp[$j];

                $items[] = ['bet_type' => 'sanrentan', 'selection_key' => "{$axis}>{$b}>{$c}", 'amount' => $amount];
                $items[] = ['bet_type' => 'sanrentan', 'selection_key' => "{$b}>{$axis}>{$c}", 'amount' => $amount];
                $items[] = ['bet_type' => 'sanrentan', 'selection_key' => "{$b}>{$c}>{$axis}", 'amount' => $amount];
            }
        }

        return $items;
    }
}
