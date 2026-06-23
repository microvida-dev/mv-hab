<?php

namespace App\Services\Notifications\Channels;

use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationReceiptType;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\User;
use App\Services\Notifications\CommunicationAttemptService;
use App\Services\Notifications\CommunicationReceiptService;
use Illuminate\Http\UploadedFile;

class PostalChannelService
{
    public function __construct(
        private readonly CommunicationAttemptService $attempts,
        private readonly CommunicationReceiptService $receipts,
    ) {}

    public function prepare(CommunicationDelivery $delivery): CommunicationDelivery
    {
        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Pending,
            'provider' => 'manual_postal',
        ])->save();

        return $delivery->refresh();
    }

    /** @param array<string, mixed> $data */
    public function registerSent(CommunicationDelivery $delivery, array $data, ?User $actor = null): CommunicationDelivery
    {
        $attempt = $this->attempts->start($delivery, $actor, 'manual_postal');
        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Sent,
            'provider' => 'manual_postal',
            'sent_at' => $data['sent_at'],
            'postal_reference' => $data['postal_reference'] ?? null,
            'postal_notes' => $data['notes'] ?? null,
        ])->save();
        $this->attempts->finish($attempt, CommunicationAttemptStatus::Success, 'Envio postal registado manualmente.');

        if (($data['receipt_file'] ?? null) instanceof UploadedFile) {
            $this->receipts->uploadPostal($delivery, $data['receipt_file'], $actor);
        } else {
            $communication = $delivery->communication;
            assert($communication instanceof CommunicationLog);
            $this->receipts->generate($communication, CommunicationReceiptType::SendProof, $delivery, $actor);
        }

        return $delivery->refresh();
    }
}
