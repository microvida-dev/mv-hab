<?php

namespace App\Http\Requests;

use App\Models\HearingSubmission;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePreliminaryHearingSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('preliminaryHearingSubmission');

        return $submission instanceof HearingSubmission
            && ($this->user()?->can('reviewBackoffice', $submission) ?? false);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:5000'],
            'accepted' => ['required', 'boolean'],
        ];
    }
}
