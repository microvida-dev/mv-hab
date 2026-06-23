<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeTaskPriority;
use App\Enums\ApplicationStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationIntakeService
{
    public function __construct(
        private readonly AdministrativeProcessService $processService,
        private readonly AdministrativeTaskService $taskService,
    ) {}

    /**
     * @return Builder<Application>
     */
    public function pendingApplications(): Builder
    {
        return Application::query()
            ->with(['user', 'program', 'contest'])
            ->whereIn('status', [
                ApplicationStatus::Submitted->value,
                ApplicationStatus::UnderReview->value,
                ApplicationStatus::CorrectionSubmitted->value,
            ])
            ->whereDoesntHave('administrativeProcess')
            ->latest('submitted_at');
    }

    public function createProcess(Application $application, User $actor): AdministrativeProcess
    {
        return DB::transaction(function () use ($application, $actor) {
            $process = $this->processService->createForApplication($application, $actor);
            $this->taskService->create($process, [
                'title' => 'Triagem inicial do processo',
                'description' => 'Confirmar receção, dados de candidatura, documentos e eventual necessidade de aperfeiçoamento.',
                'priority' => AdministrativeTaskPriority::Normal->value,
                'assigned_to' => $process->assigned_to,
                'due_at' => now()->addDays(3),
            ], $actor);

            return $process;
        });
    }

    /**
     * @return Collection<int, AdministrativeProcess>
     */
    public function createProcessesBatch(User $actor): Collection
    {
        return $this->pendingApplications()
            ->get()
            ->map(fn (Application $application) => $this->createProcess($application, $actor));
    }
}
