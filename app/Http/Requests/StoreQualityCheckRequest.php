<?php

namespace App\Http\Requests;

use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQualityCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'phase_id' => ['nullable', 'exists:project_phases,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'check_type' => ['required', Rule::in(array_keys(QualityCheck::$typeLabels))],
            'status' => ['required', Rule::in(array_keys(QualityCheck::$statusLabels))],
            'planned_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $projectId = (int) $this->input('project_id');
            $phaseId = (int) $this->input('phase_id');

            if ($projectId > 0 && $phaseId > 0) {
                $belongs = ProjectPhase::query()
                    ->where('id', $phaseId)
                    ->where('project_id', $projectId)
                    ->exists();

                if (!$belongs) {
                    $validator->errors()->add('phase_id', 'Etapa selectata nu apartine proiectului ales.');
                }
            }
        }];
    }
}
