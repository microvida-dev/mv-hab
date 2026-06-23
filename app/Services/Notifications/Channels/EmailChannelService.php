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
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailChannelService
{
    public function __construct(
        private readonly CommunicationAttemptService $attempts,
        private readonly CommunicationReceiptService $receipts,
    ) {}

    public function send(CommunicationDelivery $delivery, ?User $actor = null): CommunicationDelivery
    {
        if (! $this->isConfigured()) {
            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::PendingConfiguration,
                'provider' => config('mail.default'),
                'failure_reason' => 'Mailer externo não configurado.',
            ])->save();

            return $delivery->refresh();
        }

        $attempt = $this->attempts->start($delivery, $actor, (string) config('mail.default'));
        $communication = $delivery->communication;
        assert($communication instanceof CommunicationLog);

        try {
            Mail::html(
                nl2br(e($communication->body_snapshot)),
                function ($message) use ($delivery, $communication) {
                    $message->to($delivery->destination)
                        ->subject($communication->subject ?: $communication->title);
                },
            );
            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::Sent,
                'provider' => config('mail.default'),
                'processing_at' => now(),
                'sent_at' => now(),
            ])->save();
            $this->attempts->finish($attempt, CommunicationAttemptStatus::Success, 'Aceite pelo mailer Laravel.');
            $this->receipts->generate($communication, CommunicationReceiptType::SendProof, $delivery, $actor);
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => mb_substr($exception->getMessage(), 0, 2000),
            ])->save();
            $this->attempts->finish($attempt, CommunicationAttemptStatus::Failed, error: $exception->getMessage());
        }

        return $delivery->refresh();
    }

    private function isConfigured(): bool
    {
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');

        return $mailer !== '' && $mailer !== 'log' && $from !== '' && $from !== 'hello@example.com';
    }
}
