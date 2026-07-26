<?php

namespace App\Http\Requests;

use App\Models\ContractClause;
use Illuminate\Validation\Rule;

class UpdateContractClauseRequest extends StoreContractClauseRequest
{
    public function authorize(): bool
    {
        $clause = $this->route('contractClause');

        return $clause instanceof ContractClause
            && ($this->user()?->can('updateBackoffice', $clause) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $clause = $this->route('contractClause');
        $status = $clause instanceof ContractClause
            ? $clause->status->value
            : null;
        $rules['status'] = ['required', Rule::in(array_filter([$status]))];

        return $rules;
    }
}
