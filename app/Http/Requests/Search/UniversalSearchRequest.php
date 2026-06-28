<?php

namespace App\Http\Requests\Search;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UniversalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && ! $user->hasRole('candidate');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function term(): string
    {
        return trim((string) $this->validated('q', ''));
    }
}
