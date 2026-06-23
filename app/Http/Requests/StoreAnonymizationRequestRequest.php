<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnonymizationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('privacy.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'data_subject_request_id' => ['nullable', 'integer', 'min:1', 'exists:data_subject_requests,id'],
            'anonymization_type' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'min:10', 'max:5000'],
            'scope' => ['required', 'array', 'min:1'],
            'scope.*' => ['required', 'string', 'max:120'],
        ];
    }

    /**
     * @return array{
     *     data_subject_request_id: int|null,
     *     user_id: int|null,
     *     anonymization_type: string,
     *     reason: string,
     *     scope: array<int, string>
     * }
     */
    public function payload(): array
    {
        return [
            'data_subject_request_id' => $this->integer('data_subject_request_id') ?: null,
            'user_id' => $this->integer('user_id') ?: null,
            'anonymization_type' => $this->string('anonymization_type')->toString(),
            'reason' => $this->string('reason')->toString(),
            'scope' => $this->collect('scope')
                ->map(static fn (mixed $value): string => (string) $value)
                ->values()
                ->all(),
        ];
    }
}
