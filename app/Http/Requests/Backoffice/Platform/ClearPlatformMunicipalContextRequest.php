<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Platform;

final class ClearPlatformMunicipalContextRequest extends PlatformMunicipalContextRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'justification' => $this->justificationRules(),
            'confirm' => ['required', 'accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeJustification();
    }
}
