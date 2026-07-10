<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = TenantContext::id($this->user());

        return [
            'project_id' => ['required', Rule::exists('projects', 'id')->where('tenant_id', $tenantId)],
            'phase_id' => ['nullable', Rule::exists('project_phases', 'id')->where(function ($query) use ($tenantId) {
                $query->whereIn('project_id', function ($subQuery) use ($tenantId) {
                    $subQuery->select('id')->from('projects')->where('tenant_id', $tenantId);
                });
            })],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', 'in:todo,in_progress,done,cancelled'],
            'priority' => ['required', 'in:low,medium,high'],
            'deadline' => ['nullable', 'date'],
            'checklist' => ['nullable', 'array', 'max:30'],
            'checklist.*.text' => ['required', 'string', 'max:255'],
            'checklist.*.done' => ['nullable', 'boolean'],
            'task_materials' => ['nullable', 'array', 'max:20'],
            'task_materials.*.material_id' => ['required', 'integer', Rule::exists('materials', 'id')->where('tenant_id', $tenantId)],
            'task_materials.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'task_materials.*.unit_override' => ['nullable', 'string', 'max:50'],
            'task_materials.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }
}
