<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CriticalNotificationEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $eventCode,
        public readonly Model $related,
        public readonly array $context = [],
        public readonly ?int $actorId = null,
    ) {}
}
