<?php

namespace App\Http\Requests;

use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class StoreCorrectionResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $correctionRequest = $this->correctionRequest();

        return $correctionRequest instanceof CorrectionRequest
            && $this->user()?->can(
                'create',
                [CorrectionResponse::class, $correctionRequest],
            ) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $correctionRequest = $this->correctionRequest();
        $requestId = $this->correctionRequestKey(
            $correctionRequest,
        );

        return [
            'correction_request_item_id' => [
                'required',
                'integer',
                Rule::exists('correction_request_items', 'id')
                    ->where('correction_request_id', $requestId)
                    ->whereNull('deleted_at'),
            ],
            'response_text' => ['nullable', 'string', 'max:5000'],
            'justification' => ['nullable', 'string', 'min:20', 'max:5000'],
            'document_submission_id' => [
                'nullable',
                'integer',
                $this->candidateDocumentRule($correctionRequest),
            ],
            'reference_period' => ['nullable', 'date_format:Y-m'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:issue_date',
            ],
            'file' => [
                'nullable',
                'file',
                'max:10240',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/heic,image/heif',
                'mimes:pdf,jpg,jpeg,png,webp,heic,heif',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                ! $this->filled('response_text')
                && ! $this->filled('justification')
                && ! $this->filled('document_submission_id')
                && ! $this->hasFile('file')
            ) {
                $validator->errors()->add(
                    'response_text',
                    'Preencha o elemento solicitado, associe um documento ou apresente uma justificação.',
                );
            }
        });
    }

    private function correctionRequest(): ?CorrectionRequest
    {
        $correctionRequest = $this->route('correctionRequest');

        if ($correctionRequest instanceof CorrectionRequest) {
            return $correctionRequest;
        }

        $response = $this->route('correctionResponse');

        return $response instanceof CorrectionResponse
            ? $response->correctionRequest()->first()
            : null;
    }

    private function candidateDocumentRule(
        ?CorrectionRequest $correctionRequest,
    ): Exists {
        $userId = $this->authenticatedUserKey();
        $applicationId = $this->correctionRequestApplicationKey(
            $correctionRequest,
        );

        return Rule::exists('document_submissions', 'id')
            ->where('user_id', $userId)
            ->where('application_id', $applicationId)
            ->whereNull('deleted_at');
    }

    private function correctionRequestKey(
        ?CorrectionRequest $correctionRequest,
    ): int {
        if (! $correctionRequest instanceof CorrectionRequest) {
            return -1;
        }

        return (int) $correctionRequest->getKey();
    }

    private function correctionRequestApplicationKey(
        ?CorrectionRequest $correctionRequest,
    ): int {
        if (! $correctionRequest instanceof CorrectionRequest) {
            return -1;
        }

        return (int) $correctionRequest->application_id;
    }

    private function authenticatedUserKey(): int
    {
        $user = $this->user();

        if ($user === null) {
            return -1;
        }

        return (int) $user->getAuthIdentifier();
    }
}
