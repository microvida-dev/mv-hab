<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseTimelineItemData;
use App\Models\AuditEvent;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class CaseTimelineAggregator
{
    public function __construct(
        private readonly CaseAuthorizationService $authorization,
    ) {}

    /**
     * @return list<CaseTimelineItemData>
     */
    public function forCase(User $user, Model $case, int $limit = 40): array
    {
        $createdAt = $this->asCarbon($case->getAttribute('created_at'));
        $items = [
            new CaseTimelineItemData($createdAt, 'created', 'Processo criado', 'Registo inicial do caso.', 'processo'),
        ];

        $updatedAt = $this->asCarbon($case->getAttribute('updated_at'));
        if ($updatedAt !== null && ($createdAt === null || ! $updatedAt->equalTo($createdAt))) {
            $items[] = new CaseTimelineItemData($updatedAt, 'updated', 'Processo atualizado', 'Atualização administrativa registada.', 'processo');
        }

        $items = array_merge($items, $this->statusHistoryEvents($case));

        if ($this->authorization->hasPermission($user, 'work_tasks.view')) {
            $items = array_merge($items, $this->workTaskEvents($case));
        }

        if ($this->authorization->hasPermission($user, 'audit_logs.view')) {
            $items = array_merge($items, $this->auditEvents($case));
        }

        usort($items, fn (CaseTimelineItemData $left, CaseTimelineItemData $right): int => ($left->date?->getTimestamp() ?? 0) <=> ($right->date?->getTimestamp() ?? 0));

        return array_slice($items, 0, $limit);
    }

    /**
     * @return list<CaseTimelineItemData>
     */
    private function statusHistoryEvents(Model $case): array
    {
        if (! method_exists($case, 'statusHistories')) {
            return [];
        }

        return $case->statusHistories()
            ->limit(8)
            ->get()
            ->map(fn (Model $history): CaseTimelineItemData => new CaseTimelineItemData(
                date: $this->asCarbon($history->getAttribute('changed_at')) ?? $this->asCarbon($history->getAttribute('created_at')),
                type: 'status',
                title: 'Alteração de estado',
                description: 'Estado processual atualizado.',
                source: 'histórico',
            ))
            ->all();
    }

    /**
     * @return list<CaseTimelineItemData>
     */
    private function workTaskEvents(Model $case): array
    {
        if (! Schema::hasTable('work_tasks')) {
            return [];
        }

        return array_values(WorkTask::query()
            ->select(['id', 'type', 'status', 'created_at', 'related_type', 'related_id'])
            ->where('related_type', $case::class)
            ->where('related_id', $case->getKey())
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (WorkTask $task): CaseTimelineItemData => new CaseTimelineItemData(
                date: $this->asCarbon($task->created_at),
                type: 'work_task',
                title: 'Tarefa: '.WorkTask::typeLabel((string) $task->type),
                description: 'Tarefa operacional '.WorkTask::statusLabel((string) $task->status).'.',
                source: 'tarefas',
            ))
            ->values()
            ->all());
    }

    /**
     * @return list<CaseTimelineItemData>
     */
    private function auditEvents(Model $case): array
    {
        if (! Schema::hasTable('audit_events')) {
            return [];
        }

        return array_values(AuditEvent::query()
            ->select(['id', 'event_code', 'description', 'occurred_at', 'auditable_type', 'auditable_id'])
            ->where('auditable_type', $case::class)
            ->where('auditable_id', $case->getKey())
            ->latest('occurred_at')
            ->limit(10)
            ->get()
            ->map(fn (AuditEvent $event): CaseTimelineItemData => new CaseTimelineItemData(
                date: $this->asCarbon($event->occurred_at),
                type: 'audit',
                title: (string) $event->event_code,
                description: $this->sanitize((string) $event->description),
                source: 'auditoria',
            ))
            ->values()
            ->all());
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return $value === null ? null : Carbon::parse($value);
    }

    private function sanitize(string $value): string
    {
        $value = preg_replace('/\b\d{9}\b/', '[identificador ocultado]', $value) ?? $value;
        $value = preg_replace('/\/Users\/[^\s]+/', '[path local ocultado]', $value) ?? $value;
        $value = preg_replace('/storage(_path)?[^\s]*/i', '[path privado ocultado]', $value) ?? $value;

        return str($value)->limit(180)->toString();
    }
}
