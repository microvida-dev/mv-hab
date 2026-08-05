<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Platform;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

final class ActivatePlatformMunicipalContextRequest extends PlatformMunicipalContextRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'municipality_id' => [
                'required',
                'integer',
                Rule::exists('municipalities', 'id')
                    ->where(fn (Builder $query): Builder => $query
                        ->where('active', true)),
            ],
            'justification' => $this->justificationRules(),
            'confirm' => ['required', 'accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeJustification();
    }
}
