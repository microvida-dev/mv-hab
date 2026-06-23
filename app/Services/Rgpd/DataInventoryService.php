<?php

namespace App\Services\Rgpd;

use App\Enums\ConsentStatus;
use App\Models\User;
use App\Models\UserConsent;

class DataInventoryService
{
    /**
     * @return array<string, mixed>
     */
    public function collectForUser(User $user): array
    {
        $user->loadMissing([
            'adhesionRegistration',
            'applications',
            'consents.purpose',
            'dataSubjectRequests',
        ]);

        return [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status ?? null,
            ],
            'adhesion_registration' => $user->adhesionRegistration?->only(['id', 'status', 'municipality', 'created_at', 'updated_at']),
            'applications' => $user->applications->map->only(['id', 'application_number', 'status', 'contest_id', 'submitted_at', 'created_at'])->all(),
            'consents' => $user->consents->map(static function (UserConsent $consent): array {
                $status = $consent->getAttribute('status');

                return [
                    'purpose' => $consent->purpose?->code,
                    'status' => $status instanceof ConsentStatus ? $status->value : (is_string($status) ? $status : null),
                    'consented_at' => $consent->consented_at,
                    'withdrawn_at' => $consent->withdrawn_at,
                ];
            })->all(),
            'rgpd_requests' => $user->dataSubjectRequests->map->only(['request_number', 'request_type', 'status', 'received_at', 'due_at'])->all(),
            'notice' => 'Exportação gerada para exercício de direitos do titular. Validar identidade e enquadramento legal antes de entrega.',
        ];
    }
}
