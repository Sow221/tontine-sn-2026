<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTontineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount'      => ['required', 'integer', 'min:' . config('tontine.transaction.min_amount'), 'max:' . config('tontine.transaction.max_amount')],
            'frequency'   => ['required', 'in:daily,weekly,monthly'],
            'type'        => ['required', 'in:fixed,auction,forced_saving,ceremonial'],
            'start_date'  => ['required', 'date', 'after_or_equal:today'],
            'end_date'    => in_array($type, ['forced_saving', 'ceremonial'])
                ? ['required', 'date', 'after:start_date']
                : ['nullable', 'date', 'after:start_date'],
            'max_members' => ['required', 'integer', 'min:2', 'max:50'],
            'quorum'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'penalty_rate'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'draw_method'    => ['required', 'in:random,sequential'],
            'weighted_draw'  => ['nullable', 'boolean'],
            'veto_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'visibility'     => ['nullable', 'in:private,public'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.required' => 'La date de fin est obligatoire pour ce type de tontine.',
            'end_date.after'    => 'La date de fin doit être après la date de début.',
        ];
    }
}
