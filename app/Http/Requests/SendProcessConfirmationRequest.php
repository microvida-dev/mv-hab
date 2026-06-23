<?php

namespace App\Http\Requests;

class SendProcessConfirmationRequest extends GenerateProcessConfirmationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
