<?php

namespace App\Http\Requests;

use App\Enums\AdministrativeTaskPriority;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdministrativeTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('administrativeTask');
        if ($task instanceof AdministrativeTask) {
            return $this->user()?->can('updateBackoffice', $task) === true;
        }

        $process = $this->route('administrativeProcess');

        return $process instanceof AdministrativeProcess
            && $this->user()?->can('assignBackoffice', $process) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'priority' => ['required', 'string', Rule::in(AdministrativeTaskPriority::values())],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('municipality_id', $this->user()->municipality_id ?? -1)
                    ->where('status', 'active'),
            ],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
