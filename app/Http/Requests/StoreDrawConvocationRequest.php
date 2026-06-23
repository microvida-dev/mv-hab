<?php

namespace App\Http\Requests;

class StoreDrawConvocationRequest extends GenerateDrawConvocationsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'application_id' => ['required', 'exists:applications,id'],
            'user_id' => ['required', 'exists:users,id'],
            'lottery_participant_id' => ['nullable', 'exists:lottery_participants,id'],
        ]);
    }
}
