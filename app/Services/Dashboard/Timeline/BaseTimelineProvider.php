<?php

declare(strict_types=1);

namespace App\Services\Dashboard\Timeline;

use App\Enums\Dashboard\Timeline\TimelinePriority;
use Carbon\CarbonInterface;

abstract class BaseTimelineProvider implements TimelineProviderInterface
{
    protected function priorityFromDate(
        ?CarbonInterface $date,
        TimelinePriority $future = TimelinePriority::Medium,
        TimelinePriority $past = TimelinePriority::High,
    ): TimelinePriority {
        if ($date === null) {
            return $future;
        }

        return $date->isPast()
            ? $past
            : $future;
    }

    protected function toneFromDate(
        ?CarbonInterface $date,
        string $future = 'info',
        string $past = 'warning',
    ): string {
        if ($date === null) {
            return $future;
        }

        return $date->isPast()
            ? $past
            : $future;
    }

    protected function concat(string ...$parts): string
    {
        return implode(
            ' · ',
            array_filter(
                array_map(
                    static fn (?string $value): ?string => blank($value) ? null : trim($value),
                    $parts,
                ),
            ),
        );
    }

    protected function iso(?CarbonInterface $date): ?string
    {
        return $date?->toIso8601String();
    }

    protected function date(?CarbonInterface $date): ?string
    {
        return $date?->toDateString();
    }
}
