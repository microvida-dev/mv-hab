<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdministrativeDeadlineService
{
    public function __construct(
        private readonly AdministrativeWorkflowConfigResolver $configResolver,
        private readonly AdministrativeWorkflowTransitionService $transitionService,
    ) {}

    public function correctionDeadlineForApplication(Application $application): CarbonImmutable
    {
        $config = $this->configResolver->resolveForApplication($application);

        return now()->toImmutable()->addDays($config->default_correction_deadline_days);
    }

    /** @return Collection<int, CorrectionRequest> */
    public function markOverdueCorrections(User $actor): Collection
    {
        return CorrectionRequest::query()
            ->with('administrativeProcess')
            ->whereIn('status', [
                CorrectionRequestStatus::Issued->value,
                CorrectionRequestStatus::Open->value,
                CorrectionRequestStatus::PartiallyResponded->value,
            ])
            ->whereNotNull('response_deadline_at')
            ->where('response_deadline_at', '<', now())
            ->get()
            ->map(function (CorrectionRequest $request) use ($actor) {
                $request->forceFill(['status' => CorrectionRequestStatus::Overdue])->save();

                $process = $this->requiredAdministrativeProcess($request);

                if ($this->processStatus($process) === AdministrativeProcessStatus::AwaitingCandidateResponse) {
                    $this->transitionService->transition(
                        $process,
                        AdministrativeProcessStatus::CorrectionOverdue,
                        $actor,
                        'Prazo de resposta ao pedido de aperfeiçoamento vencido.',
                    );
                }

                return $request->refresh();
            });
    }

    private function requiredAdministrativeProcess(CorrectionRequest $request): AdministrativeProcess
    {
        $process = $request->administrativeProcess;

        if (! $process instanceof AdministrativeProcess) {
            throw ValidationException::withMessages(['process' => 'Pedido sem processo administrativo associado.']);
        }

        return $process;
    }

    private function processStatus(AdministrativeProcess $process): ?AdministrativeProcessStatus
    {
        $status = $process->getAttribute('status');

        if ($status instanceof AdministrativeProcessStatus) {
            return $status;
        }

        return is_string($status) ? AdministrativeProcessStatus::tryFrom($status) : null;
    }
}
