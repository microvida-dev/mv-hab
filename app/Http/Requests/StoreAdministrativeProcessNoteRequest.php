<?php

namespace App\Http\Requests;

use App\Enums\AdministrativeNoteVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdministrativeProcessNoteRequest extends FormRequest
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
            'visibility' => ['nullable', 'string', Rule::in(AdministrativeNoteVisibility::values())],
            'note_type' => ['nullable', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
