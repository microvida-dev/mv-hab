<?php

namespace App\Services\Cases;

use App\Models\Application;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Auth\Access\AuthorizationException;

class CaseWorkspaceService
{
    public function __construct(
        private readonly CaseAuthorizationService $authorization,
        private readonly CaseWorkspaceResolver $resolver,
        private readonly CaseSummaryService $summary,
        private readonly ProcessTimelineService $timeline,
        private readonly ProcessChecklistService $checklist,
        private readonly ProcessProgressService $progress,
        private readonly NextActionResolver $nextAction,
        private readonly ContextualCaseSearchService $search,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forApplication(User $user, Application $application, ?string $searchQuery = null): array
    {
        if (! $this->authorization->canViewCase($user, $application)) {
            throw new AuthorizationException('Processo não autorizado.');
        }

        $this->loadApplication($application);

        $tabs = $this->applicationTabs($user);
        $timeline = $this->timeline->forApplication($user, $application);
        $checklist = $this->checklist->forApplication($application);

        return [
            'case_type' => 'application',
            'supported_types' => $this->resolver->supportedTypes(),
            'summary' => $this->summary->forApplication($user, $application),
            'tabs' => $tabs,
            'timeline' => $timeline,
            'checklist' => $checklist,
            'progress' => $this->progress->forApplication($application),
            'next_action' => $this->nextAction->forApplication($user, $application),
            'sidebar' => $this->sidebar($user, $application),
            'search_results' => $this->search->search((string) $searchQuery, $timeline, $checklist, $tabs),
            'contextual_search_query' => $searchQuery,
        ];
    }

    private function loadApplication(Application $application): void
    {
        $application->loadMissing([
            'contest',
            'program',
            'household',
            'household.incomeRecords',
            'latestEligibilityCheck',
            'latestApplicationScore',
            'administrativeProcess',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function applicationTabs(User $user): array
    {
        $tabs = [
            $this->tab('summary', 'Resumo', 'applications.view'),
            $this->tab('timeline', 'Cronologia', 'applications.view'),
            $this->tab('documents', 'Documentos', 'documents.view'),
            $this->tab('eligibility', 'Elegibilidade', 'eligibility.view'),
            $this->tab('scoring', 'Pontuação', 'scoring.view'),
            $this->tab('lists', 'Listas', 'public_lists.view'),
            $this->tab('communications', 'Comunicações', 'notifications.view'),
            $this->tab('tasks', 'Tarefas', 'work_tasks.view'),
            $this->tab('visits', 'Visitas', 'visits.view'),
            $this->tab('rgpd', 'RGPD', 'privacy.view'),
            $this->tab('audit', 'Auditoria', 'audit_logs.view'),
        ];

        return array_values(array_filter(
            $tabs,
            fn (array $tab): bool => $this->authorization->canSeeItem($user, $tab),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function sidebar(User $user, Application $application): array
    {
        return [
            'open_tasks' => $application->morphMany(WorkTask::class, 'related')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'alerts' => $application->correctionRequests()->where('response_deadline_at', '<', now())->count(),
            'quick_links' => array_values(array_filter([
                $this->link($user, 'Detalhe legado', 'backoffice.applications.show', 'applications.view', [$application]),
                $this->link($user, 'Cronologia completa', 'backoffice.applications.timeline', 'applications.view', [$application]),
                $this->link($user, 'Dossier documental', 'backoffice.applications.document-dossier.show', 'documents.view', [$application]),
                $this->link($user, 'Tarefas', 'backoffice.work-tasks.index', 'work_tasks.view'),
            ])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tab(string $key, string $label, string $permission): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'permission' => $permission,
        ];
    }

    /**
     * @param  list<mixed>  $parameters
     * @return array<string, mixed>|null
     */
    private function link(User $user, string $label, string $route, string $permission, array $parameters = []): ?array
    {
        if (! $this->authorization->canSeeItem($user, ['route' => $route, 'permission' => $permission])) {
            return null;
        }

        return compact('label', 'route', 'parameters');
    }
}
