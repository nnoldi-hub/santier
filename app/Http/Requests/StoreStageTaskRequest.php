<?php

namespace App\Http\Requests;

use App\Models\StageTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStageTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'exists:project_phases,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assignee_type' => ['nullable', Rule::in(array_keys(StageTask::$assigneeTypes))],
            'assignee_id' => ['nullable', 'integer', 'min:1'],
            'deadline' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(StageTask::$statusLabels))],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $type = (string) $this->input('assignee_type');
            $id = (int) $this->input('assignee_id');

            if ($type === '' && $id > 0) {
                $validator->errors()->add('assignee_type', 'Tipul responsabilului este obligatoriu cand ai selectat un responsabil.');
                return;
            }

            if ($type !== '' && $id === 0) {
                $validator->errors()->add('assignee_id', 'Responsabilul este obligatoriu pentru tipul selectat.');
                return;
            }

            if ($type === '' || $id === 0) {
                return;
            }

            $table = match ($type) {
                'user' => 'users',
                'team' => 'teams',
                'contractor' => 'contractors',
                default => null,
            };

            if ($table === null) {
                return;
            }

            if (!\Illuminate\Support\Facades\DB::table($table)->where('id', $id)->exists()) {
                $validator->errors()->add('assignee_id', 'Responsabilul selectat nu exista pentru tipul ales.');
            }
        }];
    }
}
