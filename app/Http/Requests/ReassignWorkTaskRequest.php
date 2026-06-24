<?php

namespace App\Http\Requests;

class ReassignWorkTaskRequest extends AssignWorkTaskRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'reason' => ['required', 'string', 'max:2000'],
        ]);
    }
}
