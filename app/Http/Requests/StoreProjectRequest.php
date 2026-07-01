<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'client_id'    => ['nullable', 'exists:clients,id'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'address'      => ['nullable', 'string', 'max:500'],
            'status'       => ['required', 'in:draft,active,paused,completed,cancelled'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'total_budget' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes'        => ['nullable', 'string', 'max:5000'],
        ];
    }
}