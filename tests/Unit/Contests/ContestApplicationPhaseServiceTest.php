<?php

namespace Tests\Unit\Contests;

use App\Enums\ContestApplicationPhase;
use App\Enums\ContestDeadlineType;
use App\Enums\ContestStatus;
use App\Models\Contest;
use App\Models\ContestDeadline;
use App\Services\Contests\ContestApplicationPhaseService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ContestApplicationPhaseServiceTest extends TestCase
{
    private ContestApplicationPhaseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ContestApplicationPhaseService::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_resolves_each_processing_phase_and_the_gaps_between_them(): void
    {
        $contest = $this->contestWithCompleteTimeline();

        $this->assertSame(
            ContestApplicationPhase::Applications,
            $this->service->current($contest, '2026-09-15 12:00:00'),
        );
        $this->assertSame(
            ContestApplicationPhase::BetweenPhases,
            $this->service->current($contest, '2026-09-30 20:00:00'),
        );
        $this->assertSame(
            ContestApplicationPhase::InitialReview,
            $this->service->current($contest, '2026-10-05 12:00:00'),
        );
        $this->assertSame(
            ContestApplicationPhase::Corrections,
            $this->service->current($contest, '2026-10-20 12:00:00'),
        );
        $this->assertSame(
            ContestApplicationPhase::Revalidation,
            $this->service->current($contest, '2026-11-02 12:00:00'),
        );
        $this->assertSame(
            ContestApplicationPhase::Completed,
            $this->service->current($contest, '2026-11-11 12:00:00'),
        );
    }

    public function test_application_window_boundaries_are_inclusive(): void
    {
        $contest = $this->contestWithCompleteTimeline();

        $this->assertTrue(
            $this->service->isOpenForApplications($contest, '2026-09-01 09:00:00'),
        );
        $this->assertTrue(
            $this->service->isOpenForApplications($contest, '2026-09-30 17:00:00'),
        );
        $this->assertFalse(
            $this->service->isOpenForApplications($contest, '2026-09-30 17:00:01'),
        );
    }

    public function test_it_uses_legacy_contest_dates_when_the_application_deadline_is_not_yet_persisted(): void
    {
        $contest = (new Contest)->forceFill([
            'status' => ContestStatus::Published->value,
            'published_at' => '2026-08-01 09:00:00',
            'opens_at' => '2026-09-01 09:00:00',
            'closes_at' => '2026-09-30 17:00:00',
        ]);
        $contest->setRelation('deadlines', new Collection);

        $this->assertTrue(
            $this->service->isOpenForApplications($contest, '2026-09-15 12:00:00'),
        );
        $this->assertSame(
            ContestApplicationPhase::Applications,
            $this->service->current($contest, '2026-09-15 12:00:00'),
        );
    }

    private function contestWithCompleteTimeline(): Contest
    {
        $contest = (new Contest)->forceFill([
            'status' => ContestStatus::Published->value,
            'published_at' => '2026-08-01 09:00:00',
            'opens_at' => '2026-09-01 09:00:00',
            'closes_at' => '2026-09-30 17:00:00',
        ]);
        $contest->setRelation('deadlines', new Collection([
            $this->deadline(
                ContestDeadlineType::Applications,
                '2026-09-01 09:00:00',
                '2026-09-30 17:00:00',
            ),
            $this->deadline(
                ContestDeadlineType::Review,
                '2026-10-01 09:00:00',
                '2026-10-15 17:00:00',
            ),
            $this->deadline(
                ContestDeadlineType::Corrections,
                '2026-10-16 09:00:00',
                '2026-10-25 17:00:00',
            ),
            $this->deadline(
                ContestDeadlineType::Revalidation,
                '2026-10-26 09:00:00',
                '2026-11-10 17:00:00',
            ),
        ]));

        return $contest;
    }

    private function deadline(
        ContestDeadlineType $type,
        string $startsAt,
        string $endsAt,
    ): ContestDeadline {
        return (new ContestDeadline)->forceFill([
            'type' => $type->value,
            'label' => $type->defaultLabel(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'sort_order' => $type->processingOrder(),
        ]);
    }
}
