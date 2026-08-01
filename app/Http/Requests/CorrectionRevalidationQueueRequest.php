<?php

namespace App\Http\Requests;

use App\Enums\CorrectionRevalidationAggregateResult;
use App\Models\CorrectionRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CorrectionRevalidationQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'viewRevalidationQueue',
            CorrectionRequest::class,
        ) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'contest_id' => ['nullable', 'integer', 'exists:contests,id'],
            'submitted_from' => ['nullable', 'date_format:Y-m-d'],
            'submitted_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:submitted_from',
            ],
            'sla' => [
                'nullable',
                Rule::in(['overdue', 'due_soon', 'within_deadline']),
            ],
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'state' => [
                'nullable',
                Rule::in([
                    'awaiting_review',
                    'in_review',
                    'ready_to_seal',
                    'sealed',
                    'published',
                    'resolved',
                ]),
            ],
            'result' => [
                'nullable',
                Rule::enum(CorrectionRevalidationAggregateResult::class),
            ],
            'process_number' => ['nullable', 'string', 'max:120'],
            'application_number' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array{
     *     contest_id:int|null,
     *     submitted_from:string,
     *     submitted_to:string,
     *     sla:string,
     *     technician_id:int|null,
     *     state:string,
     *     result:string,
     *     process_number:string,
     *     application_number:string
     * }
     */
    public function filters(): array
    {
        return [
            'contest_id' => $this->validated('contest_id') !== null
                ? (int) $this->validated('contest_id')
                : null,
            'submitted_from' => trim((string) $this->validated(
                'submitted_from',
                '',
            )),
            'submitted_to' => trim((string) $this->validated(
                'submitted_to',
                '',
            )),
            'sla' => trim((string) $this->validated('sla', '')),
            'technician_id' => $this->validated('technician_id') !== null
                ? (int) $this->validated('technician_id')
                : null,
            'state' => trim((string) $this->validated('state', '')),
            'result' => trim((string) $this->validated('result', '')),
            'process_number' => trim((string) $this->validated(
                'process_number',
                '',
            )),
            'application_number' => trim((string) $this->validated(
                'application_number',
                '',
            )),
        ];
    }
}
