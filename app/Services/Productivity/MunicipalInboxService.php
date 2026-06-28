<?php

namespace App\Services\Productivity;

use App\Models\OfficialNotification;
use App\Models\User;

class MunicipalInboxService
{
    public function __construct(
        private readonly ProductivityPresenter $presenter,
        private readonly ProductivityAuthorizationService $authorization,
    ) {}

    /**
     * @return list<array{key: string, title: string, items: list<array<string, mixed>>}>
     */
    public function forUser(User $user, int $limit = 12): array
    {
        if (! $this->authorization->canSeeNotifications($user)) {
            return [];
        }

        $items = OfficialNotification::query()
            ->select(['id', 'notification_number', 'notification_type', 'priority', 'status', 'event_code', 'user_id', 'expires_at', 'created_at'])
            ->when(
                ! $user->hasRole(['administrator', 'auditor']),
                fn ($query) => $query->where('user_id', $user->id)
            )
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (OfficialNotification $notification): ?array => $this->presenter->notification($user, $notification))
            ->filter()
            ->values()
            ->all();

        $groups = [];
        foreach ($items as $item) {
            $key = str((string) ($item['category'] ?? 'Sistema'))->slug()->toString();
            $groups[$key] ??= [
                'key' => $key,
                'title' => (string) ($item['category'] ?? 'Sistema'),
                'items' => [],
            ];
            $groups[$key]['items'][] = $item;
        }

        return array_values($groups);
    }
}
