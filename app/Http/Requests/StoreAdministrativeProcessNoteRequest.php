<?php

namespace App\Http\Requests;

use App\Enums\AdministrativeNoteVisibility;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeProcessNote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdministrativeProcessNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('administrativeProcessNote');
        if ($note instanceof AdministrativeProcessNote) {
            return $this->user()?->can('updateBackoffice', $note) === true;
        }

        $process = $this->route('administrativeProcess');

        return $process instanceof AdministrativeProcess
            && $this->user()?->can('createBackoffice', $process) === true;
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
