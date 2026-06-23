<?php

namespace App\Services\Notifications\Channels;

use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationReceiptType;
use App\Enums\OfficialNotificationStatus;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\User;
use App\Services\Notifications\CommunicationAttemptService;
use App\Services\Notifications\CommunicationReceiptService;

class InAppChannelService
{
    public function __construct(
        private readonly CommunicationAttemptService $attempts,
        private readonly CommunicationReceiptService $receipts,
    ) {}

    public function send(CommunicationDelivery $delivery, ?User $actor = null): CommunicationDelivery
    {
        $attempt = $this->attempts->start($delivery, $actor, 'mvhab_in_app');
        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Delivered,
            'provider' => 'mvhab_in_app',
            'queued_at' => $delivery->queued_at ?? now(),
            'processing_at' => now(),
            'sent_at' => now(),
            'delivered_at' => now(),
        ])->save();
        $delivery->notification?->forceFill([
            'status' => OfficialNotificationStatus::Published,
            'sent_at' => now(),
            'delivered_at' => now(),
        ])->save();
        $this->attempts->finish($attempt, CommunicationAttemptStatus::Success, 'Disponibilizada na área pessoal.');
        $communication = $delivery->communication;
        assert($communication instanceof CommunicationLog);
        $this->receipts->generate($communication, CommunicationReceiptType::SendProof, $delivery, $actor);

        return $delivery->refresh();
    }
}
