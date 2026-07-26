<?php

namespace App\Http\Requests;

use App\Models\CorrectionResponse;
use Illuminate\Foundation\Http\FormRequest;

class ReviewCorrectionResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $response = $this->route('correctionResponse');

        return $response instanceof CorrectionResponse
            && $this->user()?->can('decideBackoffice', $response) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
