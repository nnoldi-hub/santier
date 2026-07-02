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
            'min_margin_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'quote_meta' => ['nullable', 'array'],
            'total_net' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'items' => ['nullable', 'array'],
            'items.*.item_type' => ['required_with:items', 'in:material,equipment,labor,custom'],
            'items.*.reference_id' => ['nullable', 'integer', 'min:1'],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.stage_name' => ['nullable', 'string', 'max:255'],
            'items.*.stage_order' => ['nullable', 'integer', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.001'],
            'items.*.cost_unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.sell_unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
