<?php

namespace App\Http\Requests;

class RunDefinitiveListAutomationRequest extends RunProvisionalListAutomationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contest_id' => ['nullable', $this->municipalContestExistsRule()],
            'confirm_complaints_reviewed' => ['accepted'],
            'confirm_snapshot_generation' => ['accepted'],
        ];
    }
}
