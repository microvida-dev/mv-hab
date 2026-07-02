<?php

namespace App\Services\Dashboard\Operations;

use App\Enums\InspectionStatus;
use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Models\WorkTask;

class TodayProvider
{
    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user, array $dashboard): array
    {
        return collect()
            ->merge($this->assignedTasks($user))
            ->merge($this->housingVisits($user))
            ->merge($this->propertyInspections($user))
            ->merge($dashboard['deadlines'] ?? [])
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assignedTasks(User $user): array
    {
        if (! $user->hasPermission('work_tasks.view')) {
            return [];
        }

        return WorkTask::query()
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', [
                WorkTask::STATUS_COMPLETED,
                WorkTask::STATUS_CANCELLED,
            ])
            ->orderByRaw('due_at IS NULL, due_at ASC')
            ->limit(5)
            ->get()
            ->map(fn (WorkTask $task): array => [
                'title' => WorkTask::typeLabel((string) $task->type),
                'description' => trim(($task->task_number ?? 'Tarefa').' · '.WorkTask::statusLabel((string) $task->status)),
                'route' => 'backoffice.work-tasks.my',
                'icon' => 'check',
                'tone' => in_array($task->priority, [WorkTask::PRIORITY_HIGH, WorkTask::PRIORITY_URGENT], true) ? 'danger' : 'warning',
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function housingVisits(User $user): array
    {
        if (! $user->hasPermission('visits.view')) {
            return [];
        }

        return HousingVisit::query()
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [
                VisitStatus::Requested->value,
                VisitStatus::PendingConfirmation->value,
                VisitStatus::Confirmed->value,
                VisitStatus::Rescheduled->value,
            ])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(fn (HousingVisit $visit): array => [
                'title' => 'Visita agendada',
                'description' => trim(($visit->visit_number ?? 'Visita').' · '.$visit->scheduled_at?->format('H:i')),
                'route' => 'backoffice.housing-visits.index',
                'icon' => 'user-inspection',
                'tone' => 'info',
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function propertyInspections(User $user): array
    {
        if (! $user->hasPermission('inspections.view')) {
            return [];
        }

        return PropertyInspection::query()
            ->whereDate('scheduled_for', today())
            ->whereIn('status', [
                InspectionStatus::Scheduled->value,
                InspectionStatus::InProgress->value,
            ])
            ->orderBy('scheduled_for')
            ->limit(5)
            ->get()
            ->map(fn (PropertyInspection $inspection): array => [
                'title' => 'Vistoria técnica',
                'description' => trim(($inspection->inspection_number ?? 'Vistoria').' · '.$inspection->scheduled_for?->format('H:i')),
                'route' => 'backoffice.inspections.index',
                'icon' => 'inspection',
                'tone' => 'info',
            ])
            ->all();
    }
}
