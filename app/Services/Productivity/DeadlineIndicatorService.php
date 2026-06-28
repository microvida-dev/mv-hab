<?php

namespace App\Services\Productivity;

use App\Models\WorkTask;
use Illuminate\Support\Carbon;

class DeadlineIndicatorService
{
    /**
     * @return array{state: string, label: string, description: string}
     */
    public function forWorkTask(WorkTask $task): array
    {
        if ($task->status === WorkTask::STATUS_COMPLETED) {
            return [
                'state' => 'completed',
                'label' => 'Concluído',
                'description' => 'Tarefa concluída.',
            ];
        }

        $dueAt = $this->asCarbon($task->getAttribute('due_at'));

        if ($dueAt === null) {
            return [
                'state' => 'neutral',
                'label' => 'Sem prazo',
                'description' => 'Sem prazo operacional definido.',
            ];
        }

        if ($task->status === WorkTask::STATUS_OVERDUE || $dueAt->isPast()) {
            return [
                'state' => 'overdue',
                'label' => 'Em atraso',
                'description' => 'Prazo ultrapassado.',
            ];
        }

        if ($dueAt->betweenIncluded(now(), now()->addDays(2))) {
            return [
                'state' => 'warning',
                'label' => 'A vencer',
                'description' => 'Prazo nas próximas 48 horas.',
            ];
        }

        return [
            'state' => 'success',
            'label' => 'Dentro do prazo',
            'description' => 'Dentro do prazo operacional.',
        ];
    }

    public function format(mixed $date): string
    {
        return $this->asCarbon($date)?->format('d/m/Y H:i') ?? 'Sem prazo';
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
