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
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, CommunicationLog>
     */
    public function dispatch(string $eventCode, Model $related, array $context = [], ?User $actor = null): Collection
    {
        $created = collect();

        foreach ($this->rules->resolve($eventCode, $context) as $rule) {
            foreach ($this->recipients->resolve($rule, $related, $context) as $recipient) {
                $created->push($this->dispatchRule($rule, $recipient, $related, $context, $actor));
            }
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function dispatchRule(NotificationEventRule $rule, User $recipient, Model $related, array $context, ?User $actor): CommunicationLog
    {
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
            requiresAcknowledgement: $rule->requires_acknowledgement || $template->requires_acknowledgement,
        );

        $notification = null;
        if (in_array($channel, [CommunicationChannel::InApp, CommunicationChannel::Internal], true)) {
            $notification = $this->notifications->createFromCommunication(
                communication: $communication,
                user: $recipient,
                type: OfficialNotificationType::tryFrom($rule->event_code) ?? OfficialNotificationType::Other,
                channel: $channel === CommunicationChannel::Internal ? OfficialNotificationChannel::Internal : OfficialNotificationChannel::InApp,
                notifiable: $related,
                actor: $actor,
                actionUrl: $context['action_url'] ?? null,
            );

            return $communication->refresh();
        }

        $preference = $recipient->notificationPreference instanceof NotificationPreference
            ? $recipient->notificationPreference
            : null;

        $destination = match ($channel) {
            CommunicationChannel::Email => $preference?->email_for_notifications ?: $recipient->email,
            CommunicationChannel::Sms => $preference?->phone_for_notifications,
            CommunicationChannel::Postal => $preference?->postal_address,
            default => null,
        };
        $delivery = $this->deliveries->create($communication, $channel, $destination, $notification);

        if ($rule->send_immediately && $rule->delay_minutes === 0) {
            $this->deliveries->execute($delivery, $actor);
        }

        return $communication->refresh();
    }
}
