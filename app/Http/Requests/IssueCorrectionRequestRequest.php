<?php

namespace App\Http\Requests;

use App\Models\CorrectionRequest;
use Illuminate\Foundation\Http\FormRequest;

class IssueCorrectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $correctionRequest = $this->route('correctionRequest');

        return $correctionRequest instanceof CorrectionRequest
            && $this->user()?->can('issueBackoffice', $correctionRequest) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm_issue' => ['accepted'],
        ];
    }
}
