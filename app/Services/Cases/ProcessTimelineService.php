<?php

namespace App\Services\Cases;

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\ProcessTracking\ProcessTimelineBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * @phpstan-type CaseTimelineItem array{date: mixed, type: string, title: string, description: string|null, source: string, actor: string|null}
 */
class ProcessTimelineService
{
    public function __construct(
        private readonly ProcessTimelineBuilder $timelineBuilder,
        private readonly CaseAuthorizationService $authorization,
    ) {}

    /**
     * @return Collection<int, CaseTimelineItem>
     */
    public function forApplication(User $user, Application $application, int $limit = 40): Collection
    {
        $events = $this->timelineBuilder->forBackoffice($application)
            ->map(fn (array $event): array => $this->timelineItem(
                $event['date'],
                (string) $event['type'],
                (string) $event['title'],
                $this->sanitizeDescription($event['description']),
                'process',
            ));

        if ($this->authorization->hasPermission($user, 'work_tasks.view')) {
            $events = $events->merge($this->workTaskEvents($application));
        }

        if ($this->authorization->hasPermission($user, 'audit_logs.view')) {
            $events = $events->merge($this->auditEvents($application));
        }

        return $events
            ->sortBy('date')
            ->values()
            ->take($limit);
    }

    /**
     * @return Collection<int, CaseTimelineItem>
     */
    private function workTaskEvents(Application $application): Collection
    {
        if (! Schema::hasTable('work_tasks')) {
            return collect();
        }

        return WorkTask::query()
            ->where('related_type', $application::class)
            ->where('related_id', $application->getKey())
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (WorkTask $task): array => $this->timelineItem(
                $task->created_at,
                'work_task',
                'Work Task: '.$task->type,
                'Tarefa operacional '.$task->status.'.',
                'work_tasks',
            ));
    }

    /**
     * @return Collection<int, CaseTimelineItem>
     */
    private function auditEvents(Application $application): Collection
    {
        if (! Schema::hasTable('audit_events')) {
            return collect();
        }

        return AuditEvent::query()
            ->where('auditable_type', $application::class)
            ->where('auditable_id', $application->getKey())
            ->latest('occurred_at')
            ->limit(10)
            ->get()
            ->map(fn (AuditEvent $event): array => $this->timelineItem(
                $event->occurred_at,
                'audit',
                (string) $event->event_code,
                $this->sanitizeDescription($event->description),
                'audit',
            ));
    }

    /**
     * @return CaseTimelineItem
     */
    private function timelineItem(mixed $date, string $type, string $title, ?string $description, string $source, ?string $actor = null): array
    {
        return [
            'date' => $date,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'source' => $source,
            'actor' => $actor,
        ];
    }

    private function sanitizeDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $description = preg_replace('/\b\d{9}\b/', '[identificador ocultado]', $description) ?? $description;
        $description = preg_replace('/storage\/app\/private[^\s]*/', '[path privado ocultado]', $description) ?? $description;

        return str($description)->limit(180)->toString();
    }
}
