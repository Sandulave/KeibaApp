<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RacePayoutStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payouts' => ['required', 'array'],
            'payouts.*' => ['array'],
            'payouts.*.*' => ['array'],
            'payouts.*.*.selection_key' => ['nullable', 'string', 'max:50'],
            'payouts.*.*.payout_per_100' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'payouts.required' => '配当データがありません。',
            'payouts.array' => '配当データの形式が不正です。',
        ];
    }
}
