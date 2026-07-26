<?php

namespace App\Http\Requests;

use App\Models\ProcedureTemplate;
use Illuminate\Foundation\Http\FormRequest;

class PublishProcedureTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('procedureTemplate');

        return $template instanceof ProcedureTemplate
            && $this->user()?->can('publishBackoffice', $template) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['confirm_publication' => ['nullable', 'boolean']];
    }
}
