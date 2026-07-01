<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'type'         => ['required', 'string'],
            'status'       => ['required', 'in:pending,in_progress,completed,blocked'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration_days'=> ['nullable', 'integer', 'min:1'],
            'progress_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'contractor_id'=> ['nullable', 'integer', 'exists:contractors,id'],
            'parent_id'    => ['nullable', 'integer', 'exists:project_phases,id'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ];
    }
}