<?php

namespace App\Services\Bet\Builders;

use App\Models\Race;
use Illuminate\Http\Request;

interface BetBuilderInterface
{
    /**
     * @param array $validated  バリデーション済み入力
     * @param Race  $race       対象レース
     * @return array            ['bet_type','selection_key','amount'] の配列
     */
    public function rules(Race $race): array;

    public function messages(): array;

    public function validate(Request $request, Race $race): array;

    public function build(array $validated, Race $race): array;
}
