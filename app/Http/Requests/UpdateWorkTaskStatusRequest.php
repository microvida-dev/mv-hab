<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && (
                $user->hasPermission('work_tasks.update_status')
                || $user->hasPermission('work_tasks.complete')
                || $user->hasPermission('work_tasks.cancel')
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    WorkTask::STATUS_IN_ANALYSIS,
                    WorkTask::STATUS_WAITING_CANDIDATE,
                    WorkTask::STATUS_WAITING_INTERNAL,
                    WorkTask::STATUS_WAITING_EXTERNAL,
                    WorkTask::STATUS_COMPLETED,
                    WorkTask::STATUS_CANCELLED,
                ]),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
            'outcome_note' => ['required_if:status,'.WorkTask::STATUS_COMPLETED, 'nullable', 'string', 'max:2000'],
            'cancellation_reason' => ['required_if:status,'.WorkTask::STATUS_CANCELLED, 'nullable', 'string', 'max:2000'],
        ];
    }
}
