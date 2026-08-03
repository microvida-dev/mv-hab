<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdministrativeDeadlineService
{
    public function __construct(
        private readonly AdministrativeWorkflowConfigResolver $configResolver,
        private readonly AdministrativeWorkflowTransitionService $transitionService,
        private readonly AuditLogger $audit,
    ) {}

    public function correctionDeadlineForApplication(
        Application $application,
    ): CarbonImmutable {
        $config = $this->configResolver
            ->resolveForApplication($application);

        return now()->toImmutable()->addDays(
            $config->default_correction_deadline_days,
        );
    }

    /** @return Collection<int, CorrectionRequest> */
    public function markOverdueCorrections(
        ?User $actor = null,
    ): Collection {
        $expired = collect();

        CorrectionRequest::query()
            ->select('id')
            ->whereIn('status', [
                CorrectionRequestStatus::Notified->value,
                CorrectionRequestStatus::Open->value,
                CorrectionRequestStatus::PartiallyCompleted->value,
            ])
            ->whereNotNull('response_deadline_at')
            ->where('response_deadline_at', '<', now())
            ->orderBy('id')
            ->lazyById(100)
            ->each(function (
                CorrectionRequest $request,
            ) use ($actor, $expired): void {
                $result = $this->expire($request, $actor);

                if ($result instanceof CorrectionRequest) {
                    $expired->push($result);
                }
            });

        return $expired;
    }

    public function expire(
        CorrectionRequest $request,
        ?User $actor = null,
    ): ?CorrectionRequest {
        return DB::transaction(function () use (
            $request,
            $actor,
        ): ?CorrectionRequest {
            $lockedRequest = CorrectionRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                ! in_array($lockedRequest->status, [
                    CorrectionRequestStatus::Notified,
                    CorrectionRequestStatus::Open,
                    CorrectionRequestStatus::PartiallyCompleted,
                ], true)
                || $lockedRequest->response_deadline_at === null
                || ! $lockedRequest->response_deadline_at->isPast()
            ) {
                return null;
            }

            $lockedRequest->forceFill([
                'status' => CorrectionRequestStatus::Expired,
                'expired_at' => now('UTC'),
            ])->save();

            $process = AdministrativeProcess::query()
                ->whereKey(
                    $lockedRequest->administrative_process_id,
                )
                ->lockForUpdate()
                ->first();

            if (! $process instanceof AdministrativeProcess) {
                throw ValidationException::withMessages([
                    'process' => 'Pedido sem processo administrativo associado.',
                ]);
            }

            if (
                $process->status
                === AdministrativeProcessStatus::AwaitingCandidateResponse
            ) {
                $this->transitionService->transition(
                    $process,
                    AdministrativeProcessStatus::CorrectionOverdue,
                    $actor,
                    'Prazo de resposta ao pedido de aperfeiçoamento vencido.',
                );
            }

            $this->audit->record(
                event: AuditEvents::UPDATE,
                auditable: $lockedRequest,
                module: 'administrative_processes',
                action: 'correction_request_expired',
                description: 'Pedido de aperfeiçoamento marcado como expirado.',
                newValues: [
                    'status' => CorrectionRequestStatus::Expired->value,
                    'expired_at' => $lockedRequest->expired_at?->toIso8601String(),
                ],
                metadata: [
                    'actor_id' => $actor?->id,
                    'system_initiated' => $actor === null,
                ],
            );

            return $lockedRequest->refresh();
        }, 3);
    }
}
