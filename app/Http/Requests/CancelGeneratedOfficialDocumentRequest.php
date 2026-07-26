<?php

namespace App\Http\Requests;

use App\Models\GeneratedOfficialDocument;
use Illuminate\Foundation\Http\FormRequest;

class CancelGeneratedOfficialDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('generatedOfficialDocument');

        return $document instanceof GeneratedOfficialDocument
            && $this->user()?->can('cancelBackoffice', $document) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['cancellation_reason' => ['required', 'string', 'max:3000']];
    }
}
