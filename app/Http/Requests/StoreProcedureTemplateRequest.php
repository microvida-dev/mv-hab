<?php

namespace App\Http\Requests;

use App\Models\ProcedureTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcedureTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('procedureTemplate');

        return $template instanceof ProcedureTemplate
            ? $this->user()?->can('updateBackoffice', $template) === true
            : $this->user()?->can(
                'createBackoffice',
                ProcedureTemplate::class,
            ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100', Rule::in(['application_report', 'document_dossier', 'provisional_list', 'definitive_list', 'procedure_minute', 'notification', 'process_confirmation', 'internal_note'])],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'content' => ['required', 'string', 'min:10'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:100'],
        ];
    }
}
