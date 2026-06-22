<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', "unique:users,email,{$userId}"],
            'phone_number' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => "L'email est obligatoire.",
            'email.unique' => 'Cet email est déjà utilisé.',
            'avatar.image' => 'Le fichier doit être une image.',
            'avatar.max' => "L'image ne doit pas dépasser 2 Mo.",
            'phone_number.regex' => 'Format de téléphone invalide (ex: +221 77 000 00 00).',
        ];
    }
}
