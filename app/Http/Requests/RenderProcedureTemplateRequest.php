<?php

namespace App\Http\Requests;

use App\Models\ProcedureTemplate;
use Illuminate\Foundation\Http\FormRequest;

class RenderProcedureTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('procedureTemplate');
        if (! $template instanceof ProcedureTemplate) {
            return false;
        }

        $ability = $this->routeIs('backoffice.procedure-templates.documents.generate')
            ? 'generateBackoffice'
            : 'previewBackoffice';

        return $this->user()?->can($ability, $template) === true;
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
