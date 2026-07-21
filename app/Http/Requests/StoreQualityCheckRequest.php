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
            'checklist' => ['nullable', 'array', 'max:40'],
            'checklist.*.text' => ['required', 'string', 'max:255'],
            'checklist.*.done' => ['nullable', 'boolean'],
            'check_type' => ['required', Rule::in(array_keys(QualityCheck::$typeLabels))],
            'reception_type' => ['required', Rule::in(array_keys(QualityCheck::$receptionTypeLabels))],
            'status' => ['required', Rule::in(array_keys(QualityCheck::$statusLabels))],
            'planned_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'signature_data_url' => ['nullable', 'string'],
            'signed_by_name' => ['nullable', 'string', 'max:255'],
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

            $status = $this->input('status');

            if (in_array($status, ['passed', 'failed'], true)) {
                $hasNewPhotos = collect($this->file('photos') ?? [])->filter()->isNotEmpty();
                $hasExistingPhotos = $this->route('quality_check')?->photos()->exists() ?? false;

                if (!$hasNewPhotos && !$hasExistingPhotos) {
                    $validator->errors()->add('photos', 'Este necesara cel putin o poza pentru a finaliza verificarea (Conform/Neconform).');
                }
            }
        }];
    }
}
