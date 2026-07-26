<?php

namespace App\Http\Requests;

use App\Models\Hearing;
use Illuminate\Foundation\Http\FormRequest;

class IssueHearingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $hearing = $this->route('hearing');

        return $hearing instanceof Hearing
            && ($this->user()?->can('issueBackoffice', $hearing) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
