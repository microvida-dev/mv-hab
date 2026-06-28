<?php

namespace App\Services\Productivity;

use App\Models\User;

class OperationalNotificationService
{
    public function __construct(private readonly MunicipalInboxService $inbox) {}

    /**
     * @return array{label: string, description: string, total: int, groups: list<array<string, mixed>>}
     */
    public function summary(User $user): array
    {
        $groups = $this->inbox->forUser($user, 12);
        $total = collect($groups)->sum(fn (array $group): int => count($group['items']));

        return [
            'label' => 'Inbox Municipal',
            'description' => $total > 0
                ? $total.' notificações operacionais agrupadas por categoria autorizada.'
                : 'Sem notificações operacionais autorizadas para apresentar.',
            'total' => $total,
            'groups' => $groups,
        ];
    }
}
