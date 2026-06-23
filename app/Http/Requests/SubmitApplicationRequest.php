<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submit', $this->route('application')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'declaration_accepted' => ['accepted'],
            'contest_rules_accepted' => ['accepted'],
            'data_processing_accepted' => ['accepted'],
            'truthfulness_accepted' => ['accepted'],
            'data_current_confirmed' => ['accepted'],
        ];
    }
}
