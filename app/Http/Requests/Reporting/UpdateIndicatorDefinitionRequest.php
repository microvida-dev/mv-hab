<?php

namespace App\Http\Requests\Reporting;

class UpdateIndicatorDefinitionRequest extends StoreIndicatorDefinitionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('indicatorDefinition')) ?? false;
    }
}
