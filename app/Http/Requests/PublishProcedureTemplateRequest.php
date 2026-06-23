<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishProcedureTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('documents', 'publish') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['confirm_publication' => ['nullable', 'boolean']];
    }
}
