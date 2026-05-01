<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTontineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount'      => ['required', 'integer', 'min:' . config('tontine.transaction.min_amount'), 'max:' . config('tontine.transaction.max_amount')],
            'frequency'   => ['required', 'in:daily,weekly,monthly'],
            'type'        => ['required', 'in:fixed,auction,forced_saving,ceremonial'],
            'start_date'  => ['required', 'date', 'after_or_equal:today'],
            'max_members' => ['required', 'integer', 'min:2', 'max:50'],
            'penalty_rate'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'draw_method' => ['required', 'in:random,sequential'],
        ];
    }
}
