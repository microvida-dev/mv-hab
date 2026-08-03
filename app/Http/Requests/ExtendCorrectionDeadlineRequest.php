<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendCorrectionDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'extended_deadline_at' => [
                'required',
                'date',
                'after:now',
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'confirm_extension' => [
                'required',
                'accepted',
            ],
        ];
    }
}
