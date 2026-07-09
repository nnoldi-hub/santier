<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePilotInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'segment' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'estimated_users' => ['required', 'integer', 'min:1', 'max:5000'],
            'customization_scope' => ['required', 'in:branding,template,approvals,white_label,custom_domain,full_enterprise'],
            'follow_up_at' => ['nullable', 'date'],
            'next_step' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
