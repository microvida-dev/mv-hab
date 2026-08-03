<?php

namespace App\Jobs;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\CommunicationReceiptType;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Municipalities\CommunicationMunicipalContextService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Notifications\CommunicationReceiptService;
use App\Services\Platform\PlatformOperatorScopeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GenerateCommunicationReceiptJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $communicationId,
        public readonly string $type,
        public readonly ?int $deliveryId = null,
        public readonly ?int $actorId = null,
        public readonly ?int $municipalityId = null,
        public readonly ?string $permissionContext = null,
        public readonly bool $systemInitiated = false,
        /** @var array<string, scalar|null> */
        public readonly array $auditMetadata = [],
    ) {}

    public function handle(
        CommunicationReceiptService $service,
        CommunicationMunicipalContextService $context,
        MunicipalRecordScopeService $municipalScope,
        PlatformOperatorScopeService $platformScope,
        AuditTrailService $audit,
    ): void {
        $communication = CommunicationLog::query()
            ->findOrFail($this->communicationId);
        $actor = $this->actorId !== null
            ? User::query()->find($this->actorId)
            : null;
        $municipalityId = $this->municipalityId
            ?? $context->forCommunication($communication);

        if ($municipalityId === null) {
            throw new HttpException(
                403,
                'A comunicação não possui contexto municipal coerente.',
            );
        }

        $systemInitiated = $this->systemInitiated
            || ($this->actorId === null && $this->permissionContext === null);
        $permissionContext = $this->permissionContext
            ?? ($systemInitiated
                ? 'system.scheduler'
                : 'communications.create');

        if ($systemInitiated) {
            abort_unless(
                $permissionContext === 'system.scheduler'
                    && (
                        ! $actor instanceof User
                        || (int) $communication->created_by
                            === (int) $actor->id
                    ),
                403,
            );
        } else {
            abort_unless(
                $actor instanceof User
                    && ($actor->status ?? 'active') === 'active'
                    && $actor->deactivated_at === null
                    && $actor->hasPermission($permissionContext)
                    && (
                        (
                            (int) $actor->municipality_id
                                === $municipalityId
                            && $municipalScope->ownsCommunicationLog(
                                $actor,
                                $communication,
                            )
                        )
                        || (
                            $actor->municipality_id === null
                            && $platformScope->hasGlobalScope($actor)
                        )
                    ),
                403,
            );
        }

        $delivery = $this->delivery();

        abort_if(
            $delivery instanceof CommunicationDelivery
                && (int) $delivery->communication_log_id
                    !== (int) $communication->id,
            403,
        );

        $receipt = $service->generate(
            $communication,
            CommunicationReceiptType::from($this->type),
            $delivery,
            $actor,
        );

        $audit->record(
            eventCode: 'communication_receipt_generated',
            auditable: $receipt,
            category: AuditEventCategory::Communications,
            severity: AuditEventSeverity::Info,
            description: 'Comprovativo de comunicação gerado em fila.',
            metadata: [
                'job' => self::class,
                'municipality_id' => $municipalityId,
                'permission_context' => $permissionContext,
                'system_initiated' => $systemInitiated,
                ...array_intersect_key(
                    $this->auditMetadata,
                    array_flip(['source', 'correlation_id']),
                ),
            ],
            actor: $actor,
            useAuthenticatedUser: false,
        );
    }

    private function delivery(): ?CommunicationDelivery
    {
        return $this->deliveryId !== null
            ? CommunicationDelivery::query()->find($this->deliveryId)
            : null;
    }
}
