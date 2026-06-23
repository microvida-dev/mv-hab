<?php

namespace App\Http\Requests\Concerns;

trait NormalizesCandidateBooleans
{
    /**
     * @param  array<int, string>  $fields
     */
    protected function normalizeBooleans(array $fields): void
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field] = $this->boolean($field);
        }

        $this->merge($values);
    }
}
