<?php

namespace Database\Seeders;

use App\Enums\OfficialNotificationType;
use App\Models\User;
use App\Services\Notifications\OfficialNotificationService;
use Illuminate\Database\Seeder;

class CommunicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $candidate = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'candidate'))->first();
        $actor = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))->first();

        if (! $candidate || $candidate->officialNotifications()->where('event_code', 'other')->exists()) {
            return;
        }

        app(OfficialNotificationService::class)->createInternal(
            user: $candidate,
            type: OfficialNotificationType::Other,
            subject: 'Comunicação de demonstração',
            body: "Esta comunicação contém apenas dados fictícios e serve para validar o centro de notificações.\n\nTEMPLATE DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL/JURÍDICA",
            actor: $actor,
            requiresAcknowledgement: true,
        );
    }
}
