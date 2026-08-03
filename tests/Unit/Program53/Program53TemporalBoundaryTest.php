<?php

namespace Tests\Unit\Program53;

use Carbon\CarbonImmutable;
use Tests\TestCase;

final class Program53TemporalBoundaryTest extends TestCase
{
    public function test_nonexistent_spring_hour_is_normalized_and_remains_iso_8601(): void
    {
        $local = CarbonImmutable::create(
            2026,
            3,
            29,
            1,
            30,
            0,
            'Europe/Lisbon',
        );

        $this->assertSame('2026-03-29 02:30:00', $local->format('Y-m-d H:i:s'));
        $this->assertSame('+01:00', $local->format('P'));
        $this->assertSame(
            '2026-03-29T01:30:00+00:00',
            $local->utc()->toIso8601String(),
        );
    }

    public function test_repeated_autumn_hour_has_two_unambiguous_utc_instants(): void
    {
        $summerOccurrence = CarbonImmutable::parse(
            '2026-10-25T01:30:00+01:00',
        )->setTimezone('Europe/Lisbon');
        $winterOccurrence = CarbonImmutable::parse(
            '2026-10-25T01:30:00+00:00',
        )->setTimezone('Europe/Lisbon');

        $this->assertSame(
            $summerOccurrence->format('Y-m-d H:i'),
            $winterOccurrence->format('Y-m-d H:i'),
        );
        $this->assertSame('+01:00', $summerOccurrence->format('P'));
        $this->assertSame('+00:00', $winterOccurrence->format('P'));
        $this->assertSame(
            3600,
            $winterOccurrence->getTimestamp() - $summerOccurrence->getTimestamp(),
        );
        $this->assertNotSame(
            $summerOccurrence->utc()->toIso8601String(),
            $winterOccurrence->utc()->toIso8601String(),
        );
    }
}
