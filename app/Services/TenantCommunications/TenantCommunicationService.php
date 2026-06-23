<?php

namespace App\Services\TenantCommunications;

use App\Enums\TenantCommunicationStatus;
use App\Enums\TenantCommunicationVisibility;
use App\Models\Contract;
use App\Models\TenantCommunication;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class TenantCommunicationService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function open(User $tenant, User $actor, array $data): TenantCommunication
    {
        return DB::transaction(function () use ($tenant, $actor, $data) {
            $contract = isset($data['lease_contract_id'])
                ? Contract::query()->whereKey($data['lease_contract_id'])->where('user_id', $tenant->id)->first()
                : null;

            $communication = TenantCommunication::query()->create([
                'user_id' => $tenant->id,
                'lease_contract_id' => $contract?->id,
                'housing_unit_id' => $contract?->housing_unit_id,
                'subject' => $data['subject'],
                'summary' => $data['summary'] ?? null,
                'status' => TenantCommunicationStatus::Open,
                'visibility' => $data['visibility'] ?? TenantCommunicationVisibility::TenantAndMunicipality,
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'] ?? 'normal',
                'opened_at' => now(),
                'last_message_at' => now(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if (! empty($data['body'])) {
                $this->message($communication, $actor, [
                    'body' => $data['body'],
                    'sender_type' => $actor->hasRole('candidate') ? 'tenant' : 'municipality',
                    'visible_to_tenant' => true,
                ]);
            }

            $this->auditLogger->record(AuditEvents::CREATE, $communication, 'communications', 'tenant_communication_opened', 'Comunicação de inquilino aberta.');

            return $communication->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function message(TenantCommunication $communication, User $actor, array $data): TenantCommunication
    {
        $communication->messages()->create([
            'user_id' => $actor->id,
            'sender_type' => $data['sender_type'] ?? ($actor->hasRole('candidate') ? 'tenant' : 'municipality'),
            'body' => $data['body'],
            'visible_to_tenant' => $data['visible_to_tenant'] ?? true,
            'created_by' => $actor->id,
        ]);

        $communication->forceFill([
            'status' => $actor->hasRole('candidate')
                ? TenantCommunicationStatus::AwaitingMunicipality
                : TenantCommunicationStatus::AwaitingTenant,
            'last_message_at' => now(),
            'updated_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(AuditEvents::UPDATE, $communication, 'communications', 'tenant_communication_message', 'Mensagem adicionada a comunicação de inquilino.');

        return $communication->refresh();
    }

    public function close(TenantCommunication $communication, User $actor): TenantCommunication
    {
        $communication->forceFill([
            'status' => TenantCommunicationStatus::Closed,
            'closed_at' => now(),
            'updated_by' => $actor->id,
        ])->save();

        return $communication->refresh();
    }
}
