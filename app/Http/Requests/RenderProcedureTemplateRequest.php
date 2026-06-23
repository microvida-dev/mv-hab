<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenderProcedureTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('documents', 'view') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['nullable', 'exists:applications,id'],
            'contest_id' => ['nullable', 'exists:contests,id'],
        ];
    }
}
