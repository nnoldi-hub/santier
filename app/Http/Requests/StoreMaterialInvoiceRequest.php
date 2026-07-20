<?php

namespace App\Http\Requests;

use App\Models\MaterialInvoice;
use App\Models\ProjectPhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialInvoiceRequest extends FormRequest
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
            'material_id' => ['nullable', 'exists:materials,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'amount_net' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'amount_vat' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'amount_total' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_status' => ['required', Rule::in(array_keys(MaterialInvoice::$paymentStatusLabels))],
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
