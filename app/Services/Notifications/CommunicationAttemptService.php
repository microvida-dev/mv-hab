<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationAttemptStatus;
use App\Models\CommunicationAttempt;
use App\Models\CommunicationDelivery;
use App\Models\User;

class CommunicationAttemptService
{
    public function start(CommunicationDelivery $delivery, ?User $actor = null, ?string $provider = null): CommunicationAttempt
    {
        $attempt = new CommunicationAttempt([
            'communication_delivery_id' => $delivery->id,
            'provider' => $provider,
        ]);
        $attempt->forceFill([
            'attempt_number' => ((int) $delivery->attempts()->max('attempt_number')) + 1,
            'status' => CommunicationAttemptStatus::Started,
            'started_at' => now(),
            'created_by' => $actor?->id,
            'created_at' => now(),
        ])->save();

        return $attempt;
    }

    public function finish(CommunicationAttempt $attempt, CommunicationAttemptStatus $status, ?string $response = null, ?string $error = null): CommunicationAttempt
    {
        $attempt->forceFill([
            'status' => $status,
            'finished_at' => now(),
            'response_payload_summary' => $response ? mb_substr($response, 0, 1000) : null,
            'error_message' => $error ? mb_substr($error, 0, 2000) : null,
        ])->save();

        return $attempt->refresh();
    }
}
