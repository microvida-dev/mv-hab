<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFutureApplicationDataReuseRequest extends FormRequest
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
            'source_reuse_profile_id' => ['required', 'exists:candidate_data_reuse_profiles,id'],
            'target_application_id' => ['nullable', 'exists:applications,id'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string', 'max:100'],
        ];
    }
}
