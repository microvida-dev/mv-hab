<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmFutureApplicationDataReuseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'target_application_id' => ['required', 'exists:applications,id'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string', 'max:100'],
            'confirm_review_required' => ['accepted'],
        ];
    }
}
