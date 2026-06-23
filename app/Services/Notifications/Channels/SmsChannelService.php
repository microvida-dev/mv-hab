<?php

namespace App\Services\Notifications\Channels;

use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use App\Models\User;
use App\Services\Notifications\CommunicationAttemptService;

class SmsChannelService
{
    public function __construct(private readonly CommunicationAttemptService $attempts) {}

    public function send(CommunicationDelivery $delivery, ?User $actor = null): CommunicationDelivery
    {
        $attempt = $this->attempts->start($delivery, $actor, 'sms_not_configured');
        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Disabled,
            'provider' => 'sms_not_configured',
            'failure_reason' => 'Gateway SMS não configurado.',
        ])->save();
        $this->attempts->finish($attempt, CommunicationAttemptStatus::Skipped, 'Canal SMS desativado; nenhum envio real ocorreu.');

        return $delivery->refresh();
    }
}
