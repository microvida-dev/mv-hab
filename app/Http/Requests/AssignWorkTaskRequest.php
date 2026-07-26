<?php

namespace App\Http\Requests;

use App\Models\WorkTask;
use Illuminate\Foundation\Http\FormRequest;

class AssignWorkTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('workTask');

        return $task instanceof WorkTask
            && $this->user()?->can('reassign', $task) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'municipal_team_id' => ['nullable', 'integer', 'exists:municipal_teams,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
