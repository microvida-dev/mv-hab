<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationChannel;
use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationType;
use App\Models\CommunicationLog;
use App\Models\NotificationEventRule;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationEventDispatcher
{
    public function __construct(
        private readonly NotificationEventRuleResolver $rules,
        private readonly RecipientResolver $recipients,
        private readonly NotificationTemplateResolver $templates,
        private readonly TemplateRenderingService $renderer,
        private readonly CommunicationLogService $communications,
        private readonly OfficialNotificationService $notifications,
        private readonly CommunicationDeliveryService $deliveries,
        private readonly ProceduralEmailDeliveryService $proceduralEmails,
        private readonly ProceduralNotificationPolicy $proceduralPolicy,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, CommunicationLog>
     */
    public function dispatch(
        string $eventCode,
        Model $related,
        array $context = [],
        ?User $actor = null,
    ): Collection {
        $created = collect();

        /**
         * @var array<int, array{
         *     recipient: User,
         *     communication: CommunicationLog,
         *     has_in_app: bool,
         *     has_email: bool
         * }>
         */
        $mandatoryCoverage = [];

        foreach ($this->rules->resolve($eventCode, $context) as $rule) {
            foreach (
                $this->recipients->resolve(
                    $rule,
                    $related,
                    $context,
                ) as $recipient
            ) {
                $communication = $this->dispatchRule(
                    $rule,
                    $recipient,
                    $related,
                    $context,
                    $actor,
                );
                $created->push($communication);

                $template = $rule->template;
                assert($template instanceof NotificationTemplate);

                if (
                    ! $this->proceduralPolicy->requiresMandatoryEmail(
                        $rule->event_code,
                        (bool) $template->is_official,
                    )
                ) {
                    continue;
                }

                $coverage = $mandatoryCoverage[$recipient->id] ?? [
                    'recipient' => $recipient,
                    'communication' => $communication,
                    'has_in_app' => false,
                    'has_email' => false,
                ];

                $coverage['has_in_app'] = $coverage['has_in_app']
                    || $rule->channel === CommunicationChannel::InApp;
                $coverage['has_email'] = $coverage['has_email']
                    || $rule->channel === CommunicationChannel::Email;
                $mandatoryCoverage[$recipient->id] = $coverage;
            }
        }

        foreach ($mandatoryCoverage as $coverage) {
            if (! $coverage['has_in_app']) {
                $this->notifications->createFromCommunication(
                    communication: $coverage['communication'],
                    user: $coverage['recipient'],
                    type: OfficialNotificationType::tryFrom($eventCode)
                        ?? OfficialNotificationType::Other,
                    channel: OfficialNotificationChannel::InApp,
                    notifiable: $related,
                    actor: $actor,
                    actionUrl: $context['action_url'] ?? null,
                    enforceMandatoryEmail: false,
                );
            }

            if (! $coverage['has_email']) {
                $this->proceduralEmails->ensureQueued(
                    $coverage['communication'],
                    $coverage['recipient'],
                );
            }
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function dispatchRule(
        NotificationEventRule $rule,
        User $recipient,
        Model $related,
        array $context,
        ?User $actor,
    ): CommunicationLog {
        $template = $rule->template;
        assert($template instanceof NotificationTemplate);
        $channel = $rule->channel;
        $version = $this->templates->resolve($template);

        $variables = $context['variables'] ?? [];
        assert(is_array($variables));

        $rendered = $this->renderer->render([
            'subject' => $version->subject,
            'title' => $version->title,
            'body' => $version->body,
            'html_body' => $version->html_body,
            'sms_body' => $version->sms_body,
        ], $variables, $channel);

        $communication = $this->communications->create(
            eventCode: $rule->event_code,
            recipient: $recipient,
            content: $rendered,
            related: $related,
            template: $template,
            version: $version,
            actor: $actor,
            priority: $rule->priority,
            official: $template->is_official,
            requiresAcknowledgement: $rule->requires_acknowledgement
                || $template->requires_acknowledgement,
        );

        if (
            in_array($channel, [
                CommunicationChannel::InApp,
                CommunicationChannel::Internal,
            ], true)
        ) {
            $this->notifications->createFromCommunication(
                communication: $communication,
                user: $recipient,
                type: OfficialNotificationType::tryFrom(
                    $rule->event_code,
                ) ?? OfficialNotificationType::Other,
                channel: $channel === CommunicationChannel::Internal
                    ? OfficialNotificationChannel::Internal
                    : OfficialNotificationChannel::InApp,
                notifiable: $related,
                actor: $actor,
                actionUrl: $context['action_url'] ?? null,
                enforceMandatoryEmail: false,
            );

            return $communication->refresh();
        }

        if (
            $channel === CommunicationChannel::Email
            && $this->proceduralPolicy->requiresMandatoryEmail(
                $rule->event_code,
                (bool) $template->is_official,
            )
        ) {
            $this->proceduralEmails->ensureQueued(
                $communication,
                $recipient,
            );

            return $communication->refresh();
        }

        $preference = $recipient->notificationPreference
            instanceof NotificationPreference
            ? $recipient->notificationPreference
            : null;

        $destination = match ($channel) {
            CommunicationChannel::Email => $preference
                ?->email_for_notifications ?: $recipient->email,
            CommunicationChannel::Sms => $preference
                ?->phone_for_notifications,
            CommunicationChannel::Postal => $preference?->postal_address,
            default => null,
        };
        $delivery = $this->deliveries->create(
            $communication,
            $channel,
            $destination,
        );

        if (
            $rule->send_immediately
            && $rule->delay_minutes === 0
        ) {
            $this->deliveries->execute($delivery, $actor);
        }

        return $communication->refresh();
    }
}
