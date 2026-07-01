<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'exists:project_phases,id'],
            'contractor_id' => ['nullable', 'exists:contractors,id'],
            'report_date' => ['required', 'date'],
            'progress_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'activities' => ['nullable', 'string', 'max:8000'],
            'issues' => ['nullable', 'string', 'max:8000'],
            'materials_used' => ['nullable', 'array'],
            'materials_used.*' => ['nullable', 'string', 'max:255'],
            'equipment_used' => ['nullable', 'array'],
            'equipment_used.*' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
