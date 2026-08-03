<?php

namespace App\Http\Requests\Reporting;

final class StoreTemporalApplicationResultExportRequest extends TemporalApplicationResultExportRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'idempotency_token' => ['required', 'uuid'],
        ];
    }
}
