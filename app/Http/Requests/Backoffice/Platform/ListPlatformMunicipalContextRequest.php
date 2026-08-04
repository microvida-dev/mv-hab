<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Platform;

use Illuminate\Validation\Rule;

final class ListPlatformMunicipalContextRequest extends PlatformMunicipalContextRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => [
                'nullable',
                'string',
                'max:100',
                'not_regex:/[\x00-\x1F\x7F]/u',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['all', 'active', 'inactive']),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $search = trim((string) $this->input('q'));
        $status = trim((string) $this->input('status', 'all'));

        $this->merge([
            'q' => $search === '' ? null : $search,
            'status' => $status === '' ? 'all' : $status,
        ]);
    }
}
