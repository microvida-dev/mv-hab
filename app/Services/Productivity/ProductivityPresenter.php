<?php

namespace App\Services\Productivity;

use App\Models\OfficialNotification;
use App\Models\User;
use App\Models\WorkTask;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProductivityPresenter
{
    public function __construct(
        private readonly DeadlineIndicatorService $deadlines,
        private readonly ProductivityAuthorizationService $authorization,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function workTask(User $user, WorkTask $task): ?array
    {
        $url = $this->authorization->routeUrl('backoffice.work-tasks.show', $task);

        if ($url === null || ! $this->authorization->canSeeRoute($user, 'backoffice.work-tasks.show', 'work_tasks.view')) {
            return null;
        }

        $deadline = $this->deadlines->forWorkTask($task);

        return [
            'id' => 'work-task-'.$task->getKey(),
            'title' => WorkTask::typeLabel((string) $task->type).' · '.$task->task_number,
            'type' => 'work_task',
            'type_label' => 'Tarefa',
            'status' => (string) $task->status,
            'status_label' => WorkTask::statusLabel((string) $task->status),
            'priority' => (string) $task->priority,
            'priority_label' => $this->priorityLabel((string) $task->priority),
            'deadline_at' => $this->deadlines->format($task->due_at),
            'deadline' => $deadline,
            'team' => $this->relatedAttribute($task, 'municipalTeam', 'name'),
            'url' => $url,
            'suggested_action' => $this->suggestedAction($task),
            'read_only' => $this->authorization->isReadOnly($user),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function notification(User $user, OfficialNotification $notification): ?array
    {
        $url = $this->authorization->routeUrl('backoffice.official-notifications.show', $notification);

        if ($url === null || ! $this->authorization->canSeeNotifications($user)) {
            return null;
        }

        $type = $notification->notification_type;
        $priority = $notification->priority;
        $status = $notification->status;

        return [
            'id' => 'notification-'.$notification->getKey(),
            'title' => $this->enumLabel($type, 'Notificação operacional'),
            'type' => 'notification',
            'type_label' => 'Notificação',
            'category' => $this->notificationCategory($notification),
            'status' => $this->enumValue($status),
            'status_label' => $this->enumLabel($status, 'Registada'),
            'priority' => $this->enumValue($priority),
            'priority_label' => $this->enumLabel($priority, 'Normal'),
            'deadline_at' => $this->deadlines->format($notification->expires_at),
            'url' => $url,
            'suggested_action' => 'Consultar notificação',
        ];
    }

    public function priorityRank(string $priority): int
    {
        return match ($priority) {
            WorkTask::PRIORITY_URGENT, 'critical' => 0,
            WorkTask::PRIORITY_HIGH => 1,
            WorkTask::PRIORITY_NORMAL => 2,
            default => 3,
        };
    }

    public function priorityLabel(string $priority): string
    {
        return match ($priority) {
            WorkTask::PRIORITY_URGENT, 'critical' => 'Urgente',
            WorkTask::PRIORITY_HIGH => 'Alta',
            WorkTask::PRIORITY_LOW => 'Baixa',
            default => 'Normal',
        };
    }

    private function suggestedAction(WorkTask $task): string
    {
        return match ((string) $task->status) {
            WorkTask::STATUS_PENDING => 'Assumir ou encaminhar',
            WorkTask::STATUS_ASSIGNED => 'Iniciar análise',
            WorkTask::STATUS_IN_ANALYSIS => 'Continuar análise',
            WorkTask::STATUS_WAITING_CANDIDATE,
            WorkTask::STATUS_WAITING_INTERNAL,
            WorkTask::STATUS_WAITING_EXTERNAL => 'Acompanhar desbloqueio',
            WorkTask::STATUS_OVERDUE => 'Regularizar prioridade',
            WorkTask::STATUS_COMPLETED => 'Consultar resultado',
            default => 'Abrir tarefa',
        };
    }

    private function notificationCategory(OfficialNotification $notification): string
    {
        $eventCode = (string) $notification->event_code;
        $type = $this->enumValue($notification->notification_type);

        if (str_contains($eventCode, 'rgpd') || str_contains($eventCode, 'privacy')) {
            return 'RGPD';
        }

        if (str_contains($eventCode, 'security') || str_contains($eventCode, 'audit')) {
            return 'Segurança';
        }

        if (str_contains($type, 'maintenance') || str_contains($type, 'visit') || str_contains($type, 'ticket')) {
            return 'Operacional';
        }

        return 'Sistema';
    }

    private function enumValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function enumLabel(mixed $value, string $fallback): string
    {
        if ($value instanceof UnitEnum && method_exists($value, 'label')) {
            $label = $value->label();

            return is_string($label) ? $label : $fallback;
        }

        return $fallback;
    }

    private function relatedAttribute(Model $model, string $relation, string $attribute): ?string
    {
        $related = $model->getRelationValue($relation);

        if (! $related instanceof Model) {
            return null;
        }

        $value = $related->getAttribute($attribute);

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }
}
