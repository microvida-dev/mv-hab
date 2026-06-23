<?php

namespace App\Http\Requests\Concerns;

trait NormalizesMaintenanceBooleans
{
    protected function prepareForValidation(): void
    {
        foreach (['is_active', 'visible_to_tenant', 'tenant_present', 'requires_follow_up', 'requires_maintenance'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
