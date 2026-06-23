<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewListAutomationRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('public_lists', 'update') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['review_notes' => ['nullable', 'string', 'max:2000']];
    }
}
