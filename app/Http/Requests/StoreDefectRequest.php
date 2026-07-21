<?php

namespace App\Http\Requests;

use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDefectRequest extends FormRequest
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
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:open,in_progress,resolved,rejected'],
            'priority' => ['required', 'in:low,medium,high'],
            'due_date' => ['nullable', 'date'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'signature_data_url' => ['nullable', 'string'],
            'signed_by_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->input('status') !== 'resolved') {
                return;
            }

            $hasNewPhotos = collect($this->file('photos') ?? [])->filter()->isNotEmpty();
            $hasExistingPhotos = $this->route('defect')?->photos()->exists() ?? false;

            if (!$hasNewPhotos && !$hasExistingPhotos) {
                $validator->errors()->add('photos', 'Este necesara cel putin o poza pentru a marca defectul ca Rezolvat.');
            }
        }];
    }
}
