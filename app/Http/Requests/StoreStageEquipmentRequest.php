<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStageEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id' => ['required', 'exists:equipment,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'usage_start' => ['nullable', 'date'],
            'usage_end' => ['nullable', 'date', 'after_or_equal:usage_start'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
