<?php

namespace App\Listeners;

use App\Events\CriticalNotificationEvent;
use App\Models\User;
use App\Services\Notifications\NotificationEventDispatcher;

class DispatchCriticalNotification
{
    public function __construct(private readonly NotificationEventDispatcher $dispatcher) {}

    public function handle(CriticalNotificationEvent $event): void
    {
        $this->dispatcher->dispatch(
            $event->eventCode,
            $event->related,
            $event->context,
            $event->actorId ? User::query()->find($event->actorId) : null,
        );
    }
}
