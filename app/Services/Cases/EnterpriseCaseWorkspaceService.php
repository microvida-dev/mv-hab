<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseHeaderData;
use App\Data\Cases\CaseSummaryData;
use App\Data\Cases\CaseTimelineItemData;
use App\Data\Cases\CaseWorkspaceData;
use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\Complaint;
use App\Models\Contest;
use App\Models\Contract;
use App\Models\DataSubjectRequest;
use App\Models\DocumentSubmission;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class EnterpriseCaseWorkspaceService
{
    public function __construct(
        private readonly CaseAuthorizationService $authorization,
        private readonly CaseTypeRegistry $registry,
        private readonly CaseTimelineAggregator $timeline,
        private readonly CaseChecklistAggregator $checklist,
        private readonly CaseRelationsService $relations,
        private readonly CaseDocumentSummaryService $documents,
        private readonly CaseTaskSummaryService $tasks,
        private readonly CaseCommunicationSummaryService $communications,
        private readonly CaseNextActionResolver $nextAction,
        private readonly CaseSearchService $search,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function forCase(User $user, string $caseType, Model $case, ?string $searchQuery = null): array
    {
        if (! $this->authorization->canViewEnterpriseCase($user, $caseType, $case)) {
            throw new AuthorizationException('Espaço de trabalho não autorizado.');
        }

        $this->loadCase($case);

        $timeline = $this->timeline->forCase($user, $case);
        $checklist = $this->checklist->forCase($caseType, $case);
        $relations = $this->relations->forCase($user, $caseType, $case);
        $documents = $this->documents->forCase($user, $caseType, $case);
        $tasks = $this->tasks->forCase($user, $case);
        $communications = $this->communications->forCase($user, $case);

        $workspace = new CaseWorkspaceData(
            caseType: $caseType,
            supportedTypes: $this->supportedTypes(),
            header: $this->header($user, $caseType, $case),
            summary: new CaseSummaryData($this->summaryItems($user, $caseType, $case)),
            tabs: $this->tabs($user, $caseType),
            timeline: $timeline,
            checklist: $checklist,
            relations: $relations,
            documents: $documents,
            tasks: $tasks,
            communications: $communications,
            history: $this->history($timeline),
            nextAction: $this->nextAction->forCase($user, $caseType, $case),
            sidebar: $this->sidebar($user, $caseType, $case),
            searchResults: $this->search->search($searchQuery, $timeline, $checklist, $relations, $documents, $tasks, $communications),
            searchQuery: $searchQuery,
        );

        return $workspace->toArray();
    }

    private function loadCase(Model $case): void
    {
        match (true) {
            $case instanceof Application => $case->loadMissing(['contest', 'program']),
            $case instanceof Contest => $case->loadMissing(['program']),
            $case instanceof Contract => $case->loadMissing(['program', 'contest', 'application', 'housingUnit']),
            $case instanceof MaintenanceRequest => $case->loadMissing(['housingUnit', 'leaseContract', 'application', 'category']),
            $case instanceof PropertyInspection => $case->loadMissing(['housingUnit', 'leaseContract', 'application', 'template']),
            $case instanceof Complaint => $case->loadMissing(['application', 'provisionalList', 'provisionalListEntry']),
            $case instanceof SupportTicket => $case->loadMissing(['application', 'contest', 'housingUnit']),
            $case instanceof HousingUnit => $case->loadMissing(['municipality']),
            $case instanceof DocumentSubmission => $case->loadMissing(['documentType', 'application', 'contract']),
            $case instanceof DataSubjectRequest => $case->loadMissing(['assignedTo']),
            $case instanceof AuditEvent => $case->loadMissing(['user']),
            default => null,
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function supportedTypes(): array
    {
        return collect($this->registry->types())
            ->map(fn (array $type): array => [
                'label' => $type['label'],
                'model' => $type['model'],
                'implemented' => true,
            ])
            ->all();
    }

    private function header(User $user, string $caseType, Model $case): CaseHeaderData
    {
        $config = $this->registry->get($caseType) ?? [];

        return new CaseHeaderData(
            type: $caseType,
            title: (string) ($config['label'] ?? 'Processo'),
            reference: $this->reference($case),
            description: $this->description($caseType, $case),
            status: $this->status($case),
            priority: $this->priority($case),
            responsible: $this->responsible($case),
            team: $this->team($caseType),
            sla: $this->sla($case),
            createdAt: $this->asCarbon($case->getAttribute('created_at')),
            updatedAt: $this->asCarbon($case->getAttribute('updated_at')),
            deadlineAt: $this->deadline($case),
            program: $this->program($case),
        );
    }

    /**
     * @return list<array{label: string, value: mixed, description?: string|null}>
     */
    private function summaryItems(User $user, string $caseType, Model $case): array
    {
        $items = [
            ['label' => 'Referência', 'value' => $this->reference($case)],
            ['label' => 'Tipo de processo', 'value' => $this->registry->get($caseType)['label'] ?? 'Processo'],
            ['label' => 'Estado', 'value' => $this->status($case)],
            ['label' => 'Prioridade', 'value' => $this->priority($case)],
            ['label' => 'Responsável', 'value' => $this->responsible($case)],
            ['label' => 'Equipa', 'value' => $this->team($caseType)],
            ['label' => 'Prazo/SLA', 'value' => $this->sla($case)],
            ['label' => 'Criado em', 'value' => $this->dateLabel($this->asCarbon($case->getAttribute('created_at')))],
        ];

        if ($case instanceof Contest) {
            $items[] = ['label' => 'Abertura', 'value' => $this->dateLabel($case->opens_at)];
            $items[] = ['label' => 'Fecho', 'value' => $this->dateLabel($case->closes_at)];
        }

        if ($case instanceof Contract) {
            $items[] = ['label' => 'Início', 'value' => $this->dateLabel($case->start_date)];
            $items[] = ['label' => 'Fim', 'value' => $this->dateLabel($case->end_date)];
            $items[] = [
                'label' => 'Renda',
                'value' => $this->canViewFinancial($user) ? $this->money($case->monthly_rent) : 'Disponível apenas a perfis financeiros autorizados',
                'description' => 'Valor administrativo/manual. Sem gateway de pagamento.',
            ];
        }

        if ($case instanceof HousingUnit) {
            $items[] = ['label' => 'Tipologia', 'value' => $case->typology ?? '—'];
            $items[] = ['label' => 'Freguesia/Zona', 'value' => trim(($case->parish ?? '—').' · '.($case->locality ?? ''))];
            $items[] = ['label' => 'Publicação', 'value' => $case->is_public ? 'Publicado' : 'Não publicado'];
        }

        if ($case instanceof DocumentSubmission) {
            $items[] = ['label' => 'Tipo documental', 'value' => $this->relatedName($case, 'documentType') ?? '—'];
            $items[] = ['label' => 'Submetido em', 'value' => $this->dateLabel($case->submitted_at)];
            $items[] = ['label' => 'Privacidade', 'value' => 'Documento privado por defeito'];
        }

        if ($case instanceof AuditEvent) {
            $items[] = ['label' => 'Categoria', 'value' => $this->enumLabel($case->event_category)];
            $items[] = ['label' => 'Severidade', 'value' => $this->enumLabel($case->severity)];
            $items[] = ['label' => 'Imutabilidade', 'value' => 'Apenas leitura'];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tabs(User $user, string $caseType): array
    {
        $config = $this->registry->get($caseType);
        $tabs = is_array($config) ? ($config['tabs'] ?? []) : [];

        return array_values(array_filter(
            $tabs,
            fn (array $tab): bool => $this->authorization->canSeeItem($user, $tab),
        ));
    }

    /**
     * @param  list<CaseTimelineItemData>  $timeline
     * @return list<CaseTimelineItemData>
     */
    private function history(array $timeline): array
    {
        return array_values(array_filter(
            $timeline,
            fn (CaseTimelineItemData $item): bool => in_array($item->source, ['auditoria', 'histórico'], true),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function sidebar(User $user, string $caseType, Model $case): array
    {
        return [
            'open_tasks' => $this->openTaskCount($case),
            'alerts' => $this->alertCount($case),
            'quick_links' => array_values(array_filter([
                $this->legacyLink($user, $caseType, $case),
                $this->routeLink($user, 'Tarefas', 'backoffice.work-tasks.index', 'work_tasks.view'),
                $this->routeLink($user, 'Auditoria', 'backoffice.security.audit.events.index', 'audit_logs.view'),
            ])),
        ];
    }

    /**
     * @return array{label: string, route: string, parameters: list<Model>}|null
     */
    private function legacyLink(User $user, string $caseType, Model $case): ?array
    {
        $config = $this->registry->get($caseType);
        $route = $config['legacy_route'] ?? null;
        $permission = $config['permission'] ?? null;

        if (! is_string($route) || ! is_string($permission) || ! $this->authorization->canSeeItem($user, ['route' => $route, 'permission' => $permission])) {
            return null;
        }

        return [
            'label' => 'Detalhe legado',
            'route' => $route,
            'parameters' => [$case],
        ];
    }

    /**
     * @return array{label: string, route: string, parameters: list<mixed>}|null
     */
    private function routeLink(User $user, string $label, string $route, string $permission): ?array
    {
        if (! $this->authorization->canSeeItem($user, ['route' => $route, 'permission' => $permission])) {
            return null;
        }

        return compact('label', 'route') + ['parameters' => []];
    }

    private function openTaskCount(Model $case): int
    {
        if (! Schema::hasTable('work_tasks')) {
            return 0;
        }

        return WorkTask::query()
            ->where('related_type', $case::class)
            ->where('related_id', $case->getKey())
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED])
            ->count();
    }

    private function alertCount(Model $case): int
    {
        if (! Schema::hasTable('work_tasks')) {
            return 0;
        }

        return WorkTask::query()
            ->where('related_type', $case::class)
            ->where('related_id', $case->getKey())
            ->where(function ($query): void {
                $query->where('status', WorkTask::STATUS_OVERDUE)
                    ->orWhere('due_at', '<', now());
            })
            ->count();
    }

    private function reference(Model $case): string
    {
        foreach (['application_number', 'public_id', 'code', 'contract_number', 'request_number', 'inspection_number', 'complaint_number', 'ticket_number', 'public_reference', 'event_number'] as $attribute) {
            $value = $case->getAttribute($attribute);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        if ($case instanceof DocumentSubmission) {
            return 'DOC-'.$case->getKey();
        }

        return 'PROC-'.$case->getKey();
    }

    private function description(string $caseType, Model $case): string
    {
        return match (true) {
            $case instanceof Contest => $this->sanitize($case->summary ?: 'Concurso municipal com fases, candidaturas, listas e publicações agregadas.'),
            $case instanceof Contract => 'Contrato municipal com documentos, vistorias, tarefas e relações autorizadas.',
            $case instanceof MaintenanceRequest => $this->sanitize($case->title ?: 'Pedido de manutenção municipal.'),
            $case instanceof PropertyInspection => 'Vistoria técnica com checklist, evidências e relatório autorizado.',
            $case instanceof Complaint => 'Reclamação administrativa acompanhada sem exposição de fundamentos sensíveis.',
            $case instanceof SupportTicket => 'Pedido de apoio acompanhado com mensagens e anexos protegidos.',
            $case instanceof HousingUnit => $this->sanitize($case->public_title ?: 'Fogo municipal com relações operacionais autorizadas.'),
            $case instanceof DocumentSubmission => 'Documento privado com validação, revisão e auditoria protegidas.',
            $case instanceof DataSubjectRequest => 'Pedido RGPD do titular com ações, prazos e evidência auditável.',
            $case instanceof AuditEvent => 'Evento de auditoria imutável apresentado sem payload bruto.',
            default => 'Caso municipal do tipo '.$caseType.'.',
        };
    }

    private function status(Model $case): string
    {
        return $this->enumLabel($case->getAttribute('status') ?? $case->getAttribute('event_category') ?? 'ativo');
    }

    private function priority(Model $case): string
    {
        $value = $case->getAttribute('priority')
            ?? $case->getAttribute('urgency')
            ?? $case->getAttribute('severity')
            ?? 'normal';

        return $this->enumLabel($value);
    }

    private function responsible(Model $case): string
    {
        foreach (['assigned_to', 'assigned_user_id', 'inspector_user_id', 'reviewed_by'] as $attribute) {
            if ($case->getAttribute($attribute) !== null) {
                return 'Responsável definido';
            }
        }

        return 'Por atribuir';
    }

    private function team(string $caseType): string
    {
        return match ($caseType) {
            'contest' => 'Concursos',
            'contract' => 'Gabinete Jurídico/Financeiro',
            'maintenance_request' => 'Manutenção',
            'inspection' => 'Vistorias',
            'complaint' => 'Gabinete Jurídico',
            'support_ticket' => 'Atendimento',
            'housing_unit' => 'Património',
            'document_case' => 'Análise documental',
            'rgpd_request' => 'DPO/RGPD',
            'audit_case' => 'Auditoria',
            default => 'Operação municipal',
        };
    }

    private function sla(Model $case): string
    {
        $deadline = $this->deadline($case);

        if ($deadline === null) {
            return 'Sem prazo definido';
        }

        return $deadline->isPast() ? 'Em atraso desde '.$deadline->format('d/m/Y') : 'Até '.$deadline->format('d/m/Y');
    }

    private function deadline(Model $case): ?Carbon
    {
        foreach (['due_at', 'closes_at', 'scheduled_for', 'additional_information_deadline_at', 'due_at', 'end_date'] as $attribute) {
            $date = $this->asCarbon($case->getAttribute($attribute));
            if ($date !== null) {
                return $date;
            }
        }

        return null;
    }

    private function program(Model $case): ?string
    {
        if ($case instanceof Contest) {
            return $this->relatedName($case, 'program');
        }

        if ($case instanceof Contract) {
            return $this->relatedName($case, 'program') ?? $this->relatedName($case, 'contest', 'title');
        }

        return null;
    }

    private function enumLabel(mixed $value): string
    {
        if (is_object($value) && method_exists($value, 'label')) {
            return (string) $value->label();
        }

        if ($value instanceof \BackedEnum) {
            return str((string) $value->value)->replace('_', ' ')->title()->toString();
        }

        return str((string) $value)->replace('_', ' ')->title()->toString();
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return $value === null ? null : Carbon::parse($value);
    }

    private function dateLabel(mixed $value): string
    {
        $date = $this->asCarbon($value);

        return $date?->format('d/m/Y') ?? '—';
    }

    private function money(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format((float) $value, 2, ',', ' ').' €';
    }

    private function relatedName(Model $case, string $relation, string $attribute = 'name'): ?string
    {
        $related = $case->getRelationValue($relation);

        if (! $related instanceof Model) {
            return null;
        }

        $value = $related->getAttribute($attribute);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function canViewFinancial(User $user): bool
    {
        return $this->authorization->hasPermission($user, 'finance.view')
            || $this->authorization->hasPermission($user, 'payments.view')
            || $this->authorization->hasPermission($user, 'reports.view_financial');
    }

    private function sanitize(string $value): string
    {
        $value = preg_replace('/\b\d{9}\b/', '[identificador ocultado]', $value) ?? $value;
        $value = preg_replace('/\/Users\/[^\s]+/', '[path local ocultado]', $value) ?? $value;
        $value = preg_replace('/storage(_path)?[^\s]*/i', '[path privado ocultado]', $value) ?? $value;

        return str($value)->limit(220)->toString();
    }
}
