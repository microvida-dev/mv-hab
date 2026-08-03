<?php

namespace App\Services\Reporting;

use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Support\AuditEvents;
use Illuminate\Auth\Access\AuthorizationException;

class ReportDefinitionService
{
    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): ReportDefinition
    {
        $this->authorize(
            $user,
            'report_definitions.create',
        );

        $report = new ReportDefinition;
        $report->forceFill($data + ['created_by' => $user->getKey(), 'updated_by' => $user->getKey()]);
        $report->save();
        $this->audit->record(
            AuditEvents::CREATE,
            $report,
            'reports',
            'report_definition_created',
            'Definição de relatório criada.',
        );

        return $report;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ReportDefinition $report, array $data, User $user): ReportDefinition
    {
        $this->authorize(
            $user,
            'report_definitions.update',
        );

        $report->forceFill($data + ['updated_by' => $user->getKey()])->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $report,
            'reports',
            'report_definition_updated',
            'Definição de relatório atualizada.',
        );

        return $report->refresh();
    }

    public function delete(ReportDefinition $report, User $user): void
    {
        $this->authorize(
            $user,
            'report_definitions.delete',
        );

        $report->delete();
        $this->audit->record(
            AuditEvents::DELETE,
            $report,
            'reports',
            'report_definition_deleted',
            'Definição de relatório arquivada.',
        );
    }

    private function authorize(User $user, string $permission): void
    {
        if (
            ! $user->hasPermission($permission)
            || ! $this->platformScope->hasGlobalScope($user)
        ) {
            throw new AuthorizationException;
        }
    }
}
