<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnonymizationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->municipality_id !== null
            && $user->hasPermission('rgpd.anonymization.request');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()?->municipality_id;

        return [
            'user_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('users', 'id')
                    ->where('municipality_id', $municipalityId),
            ],
            'data_subject_request_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('data_subject_requests', 'id')
                    ->where('municipality_id', $municipalityId),
            ],
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
