<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Application::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contest_id' => ['required', 'integer', 'exists:contests,id'],
            'candidate_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
