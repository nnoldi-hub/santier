<?php

namespace App\Http\Requests;

use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Equipment::$typeLabels))],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'cost_per_hour' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'availability_status' => ['required', Rule::in(array_keys(Equipment::$availabilityLabels))],
            'notes' => ['nullable', 'string', 'max:3000'],
            'active' => ['required', 'boolean'],
        ];
    }
}
