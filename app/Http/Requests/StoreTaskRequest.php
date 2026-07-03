<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'status' => ['required', 'in:todo,in_progress,done,cancelled'],
            'priority' => ['required', 'in:low,medium,high'],
            'deadline' => ['nullable', 'date'],
            'checklist' => ['nullable', 'array', 'max:30'],
            'checklist.*.text' => ['required', 'string', 'max:255'],
            'checklist.*.done' => ['nullable', 'boolean'],
            'task_materials' => ['nullable', 'array', 'max:20'],
            'task_materials.*.material_id' => ['required', 'integer', 'exists:materials,id'],
            'task_materials.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'task_materials.*.unit_override' => ['nullable', 'string', 'max:50'],
            'task_materials.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }
}
