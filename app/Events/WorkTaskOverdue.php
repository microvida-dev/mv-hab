<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkTaskOverdue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $taskId,
        public readonly ?int $actorId = null,
    ) {}
}
