<?php

namespace App\Services\Productivity;

use App\Models\User;
use App\Models\WorkTask;

class NextCaseService
{
    public function __construct(
        private readonly SmartActionCenterService $actionCenter,
        private readonly ProductivityPresenter $presenter,
        private readonly ProductivityAuthorizationService $authorization,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function forUser(User $user): ?array
    {
        if (! $this->authorization->canUseBackofficeProductivity($user)) {
            return null;
        }

        $task = $this->actionCenter->baseQuery($user)
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED])
            ->orderByRaw("CASE status WHEN 'overdue' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderByRaw('CASE WHEN assigned_user_id = ? THEN 0 ELSE 1 END', [$user->getKey()])
            ->orderBy('due_at')
            ->first();

        if (! $task instanceof WorkTask) {
            return null;
        }

        $item = $this->presenter->workTask($user, $task);

        if ($item === null) {
            return null;
        }

        $item['reason'] = 'Sugerido por prioridade, SLA, prazo e atribuição atual.';

        return $item;
    }
}
