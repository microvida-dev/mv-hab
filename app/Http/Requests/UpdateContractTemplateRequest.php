<?php

namespace App\Http\Requests;

use App\Models\ContractTemplate;
use Illuminate\Validation\Rule;

class UpdateContractTemplateRequest extends StoreContractTemplateRequest
{
    public function authorize(): bool
    {
        $template = $this->route('contractTemplate');

        return $template instanceof ContractTemplate
            && ($this->user()?->can('updateBackoffice', $template) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $template = $this->route('contractTemplate');
        $status = $template instanceof ContractTemplate
            ? $template->status->value
            : null;
        $rules['status'] = ['required', Rule::in(array_filter([$status]))];

        return $rules;
    }
}
