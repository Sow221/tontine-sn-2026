<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BidCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bid_rate' => ['required', 'numeric', 'min:0.5', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'bid_rate.required' => "Le taux d'enchère est obligatoire.",
            'bid_rate.min' => 'Le taux minimum est 0.5%.',
            'bid_rate.max' => 'Le taux maximum est 30%.',
        ];
    }
}
