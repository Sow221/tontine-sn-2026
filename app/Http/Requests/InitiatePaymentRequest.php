<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'in:wave,orange_money,free_money,card,cash'],
        ];
    }

    public function messages(): array
    {
        return [
            'method.required' => 'Le mode de paiement est obligatoire.',
            'method.in' => 'Mode de paiement invalide.',
        ];
    }
}
