<?php

namespace App\Http\Requests;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewWorkspaceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'process_status' => [
                'nullable',
                Rule::enum(AdministrativeProcessStatus::class),
            ],
            'review_status' => [
                'nullable',
                Rule::enum(ApplicationReviewStatus::class),
            ],
            'assigned_to' => ['nullable', 'integer'],
            'readiness' => ['nullable', Rule::in(['ready', 'not_ready'])],
        ];
    }

    /**
     * @return array{
     *     search: string,
     *     process_status: string,
     *     review_status: string,
     *     assigned_to: int|null,
     *     readiness: string
     * }
     */
    public function filters(): array
    {
        return [
            'search' => trim((string) $this->validated('search', '')),
            'process_status' => (string) $this->validated('process_status', ''),
            'review_status' => (string) $this->validated('review_status', ''),
            'assigned_to' => $this->validated('assigned_to') !== null
                ? (int) $this->validated('assigned_to')
                : null,
            'readiness' => (string) $this->validated('readiness', ''),
        ];
    }
}
