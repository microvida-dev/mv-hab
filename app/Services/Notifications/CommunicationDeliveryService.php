<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationStatus;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Notifications\Channels\EmailChannelService;
use App\Services\Notifications\Channels\InAppChannelService;
use App\Services\Notifications\Channels\InternalChannelService;
use App\Services\Notifications\Channels\PostalChannelService;
use App\Services\Notifications\Channels\SmsChannelService;
use Illuminate\Validation\ValidationException;

class CommunicationDeliveryService
{
    public function __construct(
        private readonly InAppChannelService $inApp,
        private readonly InternalChannelService $internal,
        private readonly EmailChannelService $email,
        private readonly SmsChannelService $sms,
        private readonly PostalChannelService $postal,
    ) {}

    public function create(CommunicationLog $communication, CommunicationChannel $channel, ?string $destination = null, ?OfficialNotification $notification = null): CommunicationDelivery
    {
        $delivery = new CommunicationDelivery([
            'communication_log_id' => $communication->id,
            'official_notification_id' => $notification?->id,
            'channel' => $channel,
            'destination' => $destination,
        ]);
        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Queued,
            'queued_at' => now(),
        ])->save();

        return $delivery;
    }

    public function execute(CommunicationDelivery $delivery, ?User $actor = null): CommunicationDelivery
    {
        $channel = $delivery->channel;

        $result = match ($channel) {
            CommunicationChannel::InApp => $this->inApp->send($delivery, $actor),
            CommunicationChannel::Internal => $this->internal->send($delivery, $actor),
            CommunicationChannel::Email => $this->email->send($delivery, $actor),
            CommunicationChannel::Sms => $this->sms->send($delivery, $actor),
            CommunicationChannel::Postal => $this->postal->prepare($delivery),
            CommunicationChannel::Document => $this->inApp->send($delivery, $actor),
        };
        $communication = $delivery->communication;
        assert($communication instanceof CommunicationLog);
        $this->refreshCommunicationStatus($communication);

        return $result;
    }

    public function resend(CommunicationDelivery $delivery, User $actor): CommunicationDelivery
    {
        $status = $delivery->status;

        if (in_array($status, [CommunicationDeliveryStatus::Sent, CommunicationDeliveryStatus::Delivered], true)) {
            throw ValidationException::withMessages(['delivery' => 'Uma entrega concluída não pode ser reenviada sem nova comunicação.']);
        }

        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Queued,
            'failure_reason' => null,
            'failed_at' => null,
            'cancelled_at' => null,
            'queued_at' => now(),
        ])->save();

        return $this->execute($delivery, $actor);
    }

    /** @param array<string, mixed> $data */
    public function registerPostal(CommunicationDelivery $delivery, array $data, User $actor): CommunicationDelivery
    {
        $channel = $delivery->channel;

        if ($channel !== CommunicationChannel::Postal) {
            throw ValidationException::withMessages(['delivery' => 'A entrega selecionada não é postal.']);
        }

        $result = $this->postal->registerSent($delivery, $data, $actor);
        $communication = $delivery->communication;
        assert($communication instanceof CommunicationLog);
        $this->refreshCommunicationStatus($communication);

        return $result;
    }

    private function refreshCommunicationStatus(CommunicationLog $communication): void
    {
        $statuses = $communication->deliveries()->pluck('status');

        $status = match (true) {
            $statuses->every(fn ($value) => in_array($value, [CommunicationDeliveryStatus::Sent->value, CommunicationDeliveryStatus::Delivered->value, CommunicationDeliveryStatus::Simulated->value], true)) => CommunicationStatus::Sent,
            $statuses->contains(CommunicationDeliveryStatus::Failed->value) && $statuses->count() === 1 => CommunicationStatus::Failed,
            $statuses->contains(CommunicationDeliveryStatus::Failed->value) => CommunicationStatus::PartiallySent,
            default => CommunicationStatus::Queued,
        };

        $communication->forceFill([
            'status' => $status,
            'sent_at' => $status === CommunicationStatus::Sent ? now() : $communication->sent_at,
            'failed_at' => $status === CommunicationStatus::Failed ? now() : null,
        ])->save();
    }
}
