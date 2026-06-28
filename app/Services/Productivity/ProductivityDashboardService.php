<?php

namespace App\Services\Productivity;

use App\Models\User;

class ProductivityDashboardService
{
    public function __construct(
        private readonly ProductivityAuthorizationService $authorization,
        private readonly SmartActionCenterService $actionCenter,
        private readonly MyWorkService $myWork,
        private readonly MunicipalInboxService $inbox,
        private readonly OperationalNotificationService $notifications,
        private readonly SmartQueueService $queues,
        private readonly WorkloadService $workload,
        private readonly NextCaseService $nextCase,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        if (! $this->authorization->canUseBackofficeProductivity($user)) {
            return $this->emptyPayload();
        }

        return [
            'enabled' => true,
            'action_center' => $this->actionCenter->forUser($user),
            'my_work' => $this->myWork->forUser($user),
            'inbox' => $this->inbox->forUser($user),
            'notification_summary' => $this->notifications->summary($user),
            'smart_queue' => $this->queues->forUser($user),
            'workload' => $this->workload->forUser($user),
            'next_case' => $this->nextCase->forUser($user),
            'batch_toolbar' => $this->batchToolbar(),
        ];
    }

    public function canView(User $user): bool
    {
        return $this->authorization->canUseBackofficeProductivity($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'enabled' => false,
            'action_center' => [],
            'my_work' => [],
            'inbox' => [],
            'notification_summary' => [
                'label' => 'Inbox Municipal',
                'description' => 'Sem acesso à produtividade de backoffice.',
                'total' => 0,
                'groups' => [],
            ],
            'smart_queue' => [],
            'workload' => [],
            'next_case' => null,
            'batch_toolbar' => $this->batchToolbar(),
        ];
    }

    /**
     * @return array{actions: list<array{label: string, enabled: bool, reason: string|null}>}
     */
    private function batchToolbar(): array
    {
        return [
            'actions' => [
                ['label' => 'Abrir item', 'enabled' => true, 'reason' => null],
                ['label' => 'Exportar', 'enabled' => false, 'reason' => 'Não disponível nesta versão.'],
                ['label' => 'Atribuir', 'enabled' => false, 'reason' => 'Use o fluxo autorizado de Work Tasks.'],
            ],
        ];
    }
}
