<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use App\Models\Material;
use App\Models\ProjectPhase;
use App\Models\ResourceOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceOrderRequest extends FormRequest
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
            'resource_type' => ['required', Rule::in(array_keys(ResourceOrder::$resourceTypeLabels))],
            'material_id' => ['nullable', 'integer', 'exists:materials,id'],
            'equipment_id' => ['nullable', 'integer', 'exists:equipment,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'equipment_name' => ['nullable', 'string', 'max:255'],
            'ordered_quantity' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'ordered_unit' => ['required', 'string', 'max:50'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'delivery_date' => ['nullable', 'date'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(array_keys(ResourceOrder::$statusLabels))],
            'notes' => ['nullable', 'string', 'max:4000'],
            'documents' => ['nullable', 'array', 'max:8'],
            'documents.*.title' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.type' => ['required_with:documents', Rule::in(array_keys(ResourceOrder::$documentTypeLabels))],
            'documents.*.document_number' => ['nullable', 'string', 'max:100'],
            'documents.*.declared_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'documents.*.delivered_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'documents.*.notes' => ['nullable', 'string', 'max:2000'],
            'documents.*.attachment' => ['required_with:documents', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $projectId = (int) $this->input('project_id');
            $phaseId = (int) $this->input('phase_id');
            $resourceType = (string) $this->input('resource_type');
            $materialId = (int) $this->input('material_id');
            $equipmentId = (int) $this->input('equipment_id');

            if ($projectId > 0 && $phaseId > 0) {
                $belongs = ProjectPhase::query()
                    ->where('id', $phaseId)
                    ->where('project_id', $projectId)
                    ->exists();

                if (! $belongs) {
                    $validator->errors()->add('phase_id', 'Etapa selectata nu apartine proiectului ales.');
                }
            }

            if ($resourceType === 'material' && $materialId <= 0) {
                $validator->errors()->add('material_id', 'Selecteaza materialul pentru aceasta comanda.');
            }

            if ($resourceType === 'equipment' && $equipmentId <= 0) {
                $validator->errors()->add('equipment_id', 'Selecteaza utilajul pentru aceasta comanda.');
            }

            if ($resourceType === 'material' && $materialId > 0 && ! Material::query()->whereKey($materialId)->exists()) {
                $validator->errors()->add('material_id', 'Materialul selectat nu exista.');
            }

            if ($resourceType === 'equipment' && $equipmentId > 0 && ! Equipment::query()->whereKey($equipmentId)->exists()) {
                $validator->errors()->add('equipment_id', 'Utilajul selectat nu exista.');
            }
        }];
    }
}