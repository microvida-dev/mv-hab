<?php

namespace App\Http\Requests\Reporting;

use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = match (true) {
            $this->routeIs('backoffice.reports.executive') => 'reports.view_executive',
            $this->routeIs('backoffice.reports.indicators.show') => 'indicator_definitions.view',
            default => 'reports.view',
        };

        $user = $this->user();

        return $user !== null
            && $user->hasPermission($permission)
            && app(MunicipalRecordScopeService::class)
                ->hasMunicipalOrGlobalScope($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'contest_id' => ['nullable', 'integer', 'exists:contests,id'],
            'status' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:150'],
        ];
    }
}
