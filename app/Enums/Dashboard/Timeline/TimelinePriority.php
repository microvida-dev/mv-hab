<?php

namespace App\Enums\Dashboard\Timeline;

enum TimelinePriority: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function weight(): int
    {
        return match ($this) {
            self::Critical => 10,
            self::High => 20,
            self::Medium => 40,
            self::Low => 60,
        };
    }
}
