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
        $rules = [
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
            'type_data' => ['nullable', 'array'],
        ];

        return array_merge($rules, $this->typeDataRules((string) $this->input('type')));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function typeDataRules(string $type): array
    {
        return match ($type) {
            'proc_verbal_receptie' => [
                'type_data.comisie' => ['required', 'string', 'max:2000'],
                'type_data.descriere_lucrari' => ['required', 'string', 'max:4000'],
                'type_data.defecte' => ['nullable', 'string', 'max:4000'],
                'type_data.concluzie' => ['required', Rule::in(['admis', 'respins'])],
            ],
            'proc_verbal_lucrari_ascunse' => [
                'type_data.descriere_lucrari_ascunse' => ['required', 'string', 'max:4000'],
                'type_data.verificari_efectuate' => ['required', 'string', 'max:4000'],
                'type_data.responsabil_tehnic' => ['required', 'string', 'max:255'],
            ],
            'proc_verbal_predare_primire' => [
                'type_data.predat_de' => ['required', 'string', 'max:255'],
                'type_data.primit_de' => ['required', 'string', 'max:255'],
                'type_data.obiecte' => ['required', 'string', 'max:4000'],
                'type_data.stare' => ['required', 'string', 'max:4000'],
            ],
            'proc_verbal_remediere_defecte' => [
                'type_data.defect_identificat' => ['required', 'string', 'max:4000'],
                'type_data.responsabil_remediere' => ['required', 'string', 'max:255'],
                'type_data.termen' => ['required', 'date'],
                'type_data.stare_remediere' => ['required', Rule::in(['remediat', 'nerezolvat'])],
            ],
            'proc_verbal_constatare' => [
                'type_data.situatie_constatata' => ['required', 'string', 'max:4000'],
                'type_data.martori' => ['required', 'string', 'max:2000'],
                'type_data.masuri_recomandate' => ['nullable', 'string', 'max:4000'],
            ],
            'contract' => [
                'type_data.parti_contractante' => ['required', 'string', 'max:2000'],
                'type_data.obiect_contract' => ['required', 'string', 'max:4000'],
                'type_data.termene' => ['required', 'string', 'max:4000'],
                'type_data.penalitati' => ['required', 'string', 'max:4000'],
            ],
            default => [],
        };
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
