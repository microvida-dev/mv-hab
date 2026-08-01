<?php

namespace App\Services\Administrative;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestStatus;
use App\Models\AdministrativeProcess;
use App\Models\CorrectionDeadlineExtension;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectionDeadlineExtensionService
{
    public function __construct(
        private readonly AdministrativeWorkflowTransitionService $transitions,
        private readonly AuditLogger $audit,
    ) {}

    public function extend(
        CorrectionRequest $request,
        CarbonInterface $extendedDeadline,
        string $reason,
        User $actor,
    ): CorrectionDeadlineExtension {
        return DB::transaction(function () use (
            $request,
            $extendedDeadline,
            $reason,
            $actor,
        ): CorrectionDeadlineExtension {
            $lockedRequest = CorrectionRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedRequest->isLegacy()
                || ! in_array($lockedRequest->status, [
                    CorrectionRequestStatus::Notified,
                    CorrectionRequestStatus::Open,
                    CorrectionRequestStatus::PartiallyCompleted,
                    CorrectionRequestStatus::Expired,
                ], true)
            ) {
                throw ValidationException::withMessages([
                    'extended_deadline_at' => 'O estado atual do pedido não permite prorrogação.',
                ]);
            }

            $previousDeadline = $lockedRequest->response_deadline_at;

            if ($previousDeadline === null) {
                throw ValidationException::withMessages([
                    'extended_deadline_at' => 'O pedido não possui um prazo formal configurado.',
                ]);
            }

            $newDeadline = $extendedDeadline->toImmutable();

            if (
                $newDeadline->lessThanOrEqualTo($previousDeadline)
                || $newDeadline->lessThanOrEqualTo(now())
            ) {
                throw ValidationException::withMessages([
                    'extended_deadline_at' => 'A nova data deve ser futura e posterior ao prazo atual.',
                ]);
            }

            $originalDeadline = $lockedRequest
                ->original_response_deadline_at
                ?? $previousDeadline;

            $extension = new CorrectionDeadlineExtension([
                'reason' => trim($reason),
            ]);
            $extension->forceFill([
                'correction_request_id' => $lockedRequest->id,
                'original_deadline_at' => $originalDeadline,
                'previous_deadline_at' => $previousDeadline,
                'extended_deadline_at' => $newDeadline,
                'authorized_by' => $actor->id,
                'authorized_at' => now('UTC'),
            ])->save();

            $hasPreparedResponses = $lockedRequest->responses()
                ->whereNotNull('prepared_at')
                ->exists();

            $lockedRequest->forceFill([
                'original_response_deadline_at' => $originalDeadline,
                'response_deadline_at' => $newDeadline,
                'deadline_extension_count' => ((int) $lockedRequest->deadline_extension_count) + 1,
                'status' => $hasPreparedResponses
                    ? CorrectionRequestStatus::PartiallyCompleted
                    : CorrectionRequestStatus::Open,
                'expired_at' => null,
            ])->save();

            $process = AdministrativeProcess::query()
                ->whereKey($lockedRequest->administrative_process_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $process->status
                === AdministrativeProcessStatus::CorrectionOverdue
            ) {
                $this->transitions->transition(
                    $process,
                    AdministrativeProcessStatus::AwaitingCandidateResponse,
                    $actor,
                    'Prazo do pedido de aperfeiçoamento prorrogado.',
                );
            }

            $this->audit->record(
                event: AuditEvents::UPDATE,
                auditable: $lockedRequest,
                module: 'administrative_processes',
                action: 'correction_deadline_extended',
                description: 'Prazo individual do pedido de aperfeiçoamento prorrogado.',
                oldValues: [
                    'response_deadline_at' => $previousDeadline->toIso8601String(),
                ],
                newValues: [
                    'response_deadline_at' => $newDeadline->toIso8601String(),
                    'deadline_extension_count' => $lockedRequest->deadline_extension_count,
                ],
                metadata: [
                    'actor_id' => $actor->id,
                    'extension_id' => $extension->id,
                    'reason' => trim($reason),
                ],
            );

            return $extension->refresh();
        }, 3);
    }
}
