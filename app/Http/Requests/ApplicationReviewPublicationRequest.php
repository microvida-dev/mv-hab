<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApplicationReviewPublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'preview_token' => [
                'nullable',
                'string',
                'size:64',
                'regex:/\A[a-f0-9]{64}\z/',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->routeIs(
                'backoffice.application-review-publications.publish',
            ) && trim((string) $this->input('preview_token')) === '') {
                $validator->errors()->add(
                    'preview_token',
                    'A publicação deve ser previamente confirmada.',
                );
            }
        });
    }

    /** @return array{reason: string, preview_token: string|null} */
    public function payload(): array
    {
        $validated = $this->validated();
        $token = trim((string) ($validated['preview_token'] ?? ''));

        return [
            'reason' => trim((string) $validated['reason']),
            'preview_token' => $token === '' ? null : $token,
        ];
    }
}
