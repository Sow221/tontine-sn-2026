<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTontineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tontine  = $this->route('tontine');
        $isActive = $tontine?->status === 'active';

        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type'        => $isActive ? ['prohibited'] : ['required', 'in:fixed,auction,forced_saving,ceremonial'],
            'max_members' => ['required', 'integer', 'min:' . ($tontine?->activeMembers()->count() ?? 2), 'max:50'],
            'quorum'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'penalty_rate'=> ['nullable', 'numeric', 'min:0', 'max:100'],
            'draw_method'    => $isActive ? ['prohibited'] : ['required', 'in:random,sequential'],
            'weighted_draw'  => ['nullable', 'boolean'],
            'veto_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'amount'      => $isActive ? ['prohibited'] : ['required', 'integer', 'min:' . config('tontine.transaction.min_amount'), 'max:' . config('tontine.transaction.max_amount')],
            'frequency'   => $isActive ? ['prohibited'] : ['required', 'in:daily,weekly,monthly'],
            'start_date'  => $isActive ? ['prohibited'] : ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_members.min' => 'Le nombre maximum de membres ne peut pas être inférieur au nombre de membres actifs actuels.',
        ];
    }
}
