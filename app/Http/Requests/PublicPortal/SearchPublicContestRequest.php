<?php

namespace App\Http\Requests\PublicPortal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchPublicContestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['open', 'upcoming', 'closed'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return collect($this->validated())
            ->filter(fn (mixed $value) => $value !== null && $value !== '')
            ->all();
    }
}
