<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => 'Le numéro de téléphone est obligatoire.',
            'phone_number.regex'    => 'Format de numéro invalide.',
        ];
    }
}
