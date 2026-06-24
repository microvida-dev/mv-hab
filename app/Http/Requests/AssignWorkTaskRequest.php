<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AssignWorkTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && ($user->hasPermission('work_tasks.assign') || $user->hasPermission('work_tasks.reassign'));
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
