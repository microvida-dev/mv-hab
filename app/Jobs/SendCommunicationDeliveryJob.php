<?php

namespace App\Jobs;

use App\Models\CommunicationDelivery;
use App\Models\User;
use App\Services\Municipalities\CommunicationMunicipalContextService;
use App\Services\Notifications\CommunicationDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SendCommunicationDeliveryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $deliveryId,
        public readonly ?int $actorId = null,
        public readonly ?int $municipalityId = null,
        public readonly ?string $permissionContext = null,
        public readonly bool $systemInitiated = false,
        /** @var array<string, scalar|null> */
        public readonly array $auditMetadata = [],
    ) {}

    public function handle(
        CommunicationDeliveryService $service,
        CommunicationMunicipalContextService $context,
    ): void {
        $delivery = CommunicationDelivery::query()->findOrFail($this->deliveryId);
        $actor = $this->actorId !== null
            ? User::query()->find($this->actorId)
            : null;
        $municipalityId = $this->municipalityId
            ?? $context->forDelivery($delivery);

        if ($municipalityId === null) {
            throw new HttpException(
                403,
                'A entrega não possui contexto municipal coerente.',
            );
        }

        $systemInitiated = $this->systemInitiated
            || ($this->actorId === null && $this->permissionContext === null);
        $permissionContext = $this->permissionContext
            ?? ($systemInitiated
                ? 'system.scheduler'
                : 'communications.create');

        $service->executeQueued(
            delivery: $delivery,
            actor: $actor,
            municipalityId: $municipalityId,
            permissionContext: $permissionContext,
            systemInitiated: $systemInitiated,
            auditMetadata: [
                ...$this->auditMetadata,
                'job' => self::class,
            ],
        );
    }
}
