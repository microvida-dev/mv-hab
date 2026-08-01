<?php

namespace App\Http\Requests;

use App\Enums\CorrectionResponseReviewResult;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewCorrectionRevalidationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('correctionRequest');
        $response = $this->route('correctionResponse');

        return $request instanceof CorrectionRequest
            && $response instanceof CorrectionResponse
            && (int) $response->correction_request_id === (int) $request->id
            && $this->user()?->can(
                'decideRevalidationBackoffice',
                $response,
            ) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'result' => [
                'required',
                Rule::in([
                    CorrectionResponseReviewResult::Accepted->value,
                    CorrectionResponseReviewResult::Rejected->value,
                    CorrectionResponseReviewResult::NotApplicable->value,
                    CorrectionResponseReviewResult::RequiresManualDecision->value,
                ]),
            ],
            'review_notes' => ['required', 'string', 'max:5000'],
            'source_fingerprint' => [
                'required',
                'string',
                'size:64',
                'regex:/\A[a-f0-9]{64}\z/',
            ],
            'expected_decision_token' => [
                'nullable',
                'string',
                'size:64',
                'regex:/\A[a-f0-9]{64}\z/',
            ],
        ];
    }

    /**
     * @return array{
     *     result:CorrectionResponseReviewResult,
     *     review_notes:string,
     *     source_fingerprint:string,
     *     expected_decision_token:string|null
     * }
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $expected = trim((string) ($validated['expected_decision_token'] ?? ''));

        return [
            'result' => CorrectionResponseReviewResult::from(
                (string) $validated['result'],
            ),
            'review_notes' => trim((string) $validated['review_notes']),
            'source_fingerprint' => (string) $validated['source_fingerprint'],
            'expected_decision_token' => $expected === '' ? null : $expected,
        ];
    }
}
