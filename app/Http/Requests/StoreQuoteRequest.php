<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,sent,accepted,rejected'],
            'valid_until' => ['nullable', 'date'],
            'discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tva_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'total_net' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
    }
}
