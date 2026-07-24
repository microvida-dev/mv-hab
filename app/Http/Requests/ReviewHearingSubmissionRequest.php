<?php

namespace App\Http\Requests;

use App\Models\HearingSubmission;
use Illuminate\Foundation\Http\FormRequest;

class ReviewHearingSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('hearingSubmission');

        if (! $submission instanceof HearingSubmission) {
            return false;
        }

        $ability = $this->routeIs('backoffice.hearing-submissions.accept')
            ? 'acceptBackoffice'
            : 'rejectBackoffice';

        return $this->user()?->can($ability, $submission) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accepted' => ['nullable', 'boolean'],
            'review_result' => ['nullable', 'string', 'max:100'],
            'review_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
