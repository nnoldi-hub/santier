<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'min_stock_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'active' => ['required', 'boolean'],
        ];
    }
}
