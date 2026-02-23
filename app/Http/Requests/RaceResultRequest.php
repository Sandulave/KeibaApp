<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RaceResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ranks.1' => ['required', 'array', 'min:1'],
            'ranks.1.*' => ['required', 'integer', 'distinct'],
            'ranks.2' => ['nullable', 'array'],
            'ranks.2.*' => ['required_with:ranks.2', 'integer', 'distinct'],
            'ranks.3' => ['nullable', 'array'],
            'ranks.3.*' => ['required_with:ranks.3', 'integer', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $all = $this->input('ranks', []);
            $used = [];
            foreach ($all as $rank => $horses) {
                foreach ($horses as $horse) {
                    if (in_array($horse, $used, true)) {
                        $validator->errors()->add('ranks', '同じ馬番が複数の着順に含まれています: '.$horse);
                    }
                    $used[] = $horse;
                }
            }
        });
    }
}
