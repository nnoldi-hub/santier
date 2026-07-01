<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Models\ProjectPhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Document::$typeLabels))],
            'project_id' => ['required', 'exists:projects,id'],
            'stage_id' => ['nullable', 'exists:project_phases,id'],
            'contractor_id' => ['nullable', 'exists:contractors,id'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'issued_at' => ['required', 'date'],
            'payment_status' => ['required', Rule::in(array_keys(Document::$paymentStatusLabels))],
            'notes' => ['nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,xlsx,xls,csv,doc,docx,png,jpg,jpeg', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $projectId = (int) $this->input('project_id');
            $stageId = (int) $this->input('stage_id');

            if ($projectId > 0 && $stageId > 0) {
                $belongs = ProjectPhase::query()
                    ->where('id', $stageId)
                    ->where('project_id', $projectId)
                    ->exists();

                if (!$belongs) {
                    $validator->errors()->add('stage_id', 'Etapa selectata nu apartine proiectului ales.');
                }
            }
        }];
    }
}
