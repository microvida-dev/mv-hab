<?php

namespace App\Services\Productivity;

use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Support\Carbon;

class WorkloadService
{
    public function __construct(
        private readonly SmartActionCenterService $actionCenter,
        private readonly ProductivityAuthorizationService $authorization,
    ) {}

    /**
     * @return list<array{name: string, team: string|null, total: int, overdue: int, due_soon: int, relative_load: string}>
     */
    public function forUser(User $user, int $limit = 8): array
    {
        if (! $this->authorization->canSeeWorkload($user)) {
            return [];
        }

        $tasks = $this->actionCenter->baseQuery($user)
            ->whereNotNull('assigned_user_id')
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED])
            ->limit(150)
            ->get();

        $groups = $tasks->groupBy('assigned_user_id');
        $max = max(1, (int) $groups->map->count()->max());
        $items = [];

        foreach ($groups as $group) {
            /** @var WorkTask|null $first */
            $first = $group->first();

            if (! $first instanceof WorkTask) {
                continue;
            }

            $total = (int) $group->count();
            $overdue = 0;
            $dueSoon = 0;

            /** @var WorkTask $task */
            foreach ($group as $task) {
                $dueAt = $this->asCarbon($task->getAttribute('due_at'));

                if ($task->status === WorkTask::STATUS_OVERDUE || ($dueAt !== null && $dueAt->isPast())) {
                    $overdue++;
                }

                if ($dueAt !== null && $dueAt->betweenIncluded(now(), now()->addDays(2))) {
                    $dueSoon++;
                }
            }

            $assignedUser = $first->assignedUser;

            $items[] = [
                'name' => $assignedUser instanceof User ? $assignedUser->name : 'Sem responsável',
                'team' => $first->municipalTeam?->name,
                'total' => $total,
                'overdue' => $overdue,
                'due_soon' => $dueSoon,
                'relative_load' => (string) max(10, min(100, (int) round(($total / $max) * 100))).'%',
            ];
        }

        usort($items, fn (array $left, array $right): int => $right['total'] <=> $left['total']);

        return array_slice($items, 0, $limit);
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}
