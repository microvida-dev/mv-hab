<?php

namespace Tests\Unit\Contests;

use App\Enums\ContestDeadlineType;
use App\Models\Contest;
use App\Models\ContestDeadline;
use App\Services\Contests\ContestApplicationTimelineService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContestApplicationTimelineServiceTest extends TestCase
{
    private ContestApplicationTimelineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ContestApplicationTimelineService::class);
    }

    public function test_it_normalizes_the_application_window_as_the_first_canonical_deadline(): void
    {
        $deadlines = $this->service->normalize(
            '2026-09-01 09:00:00',
            '2026-09-30 17:00:00',
            [
                [
                    'type' => ContestDeadlineType::Corrections->value,
                    'label' => 'Aperfeiçoamento',
                    'starts_at' => '2026-10-16 09:00:00',
                    'ends_at' => '2026-10-25 17:00:00',
                ],
                [
                    'type' => ContestDeadlineType::Review->value,
                    'label' => 'Análise inicial',
                    'starts_at' => '2026-10-01 09:00:00',
                    'ends_at' => '2026-10-15 17:00:00',
                ],
            ],
        );

        $this->assertSame([
            ContestDeadlineType::Applications->value,
            ContestDeadlineType::Review->value,
            ContestDeadlineType::Corrections->value,
        ], array_column($deadlines, 'type'));
        $this->assertSame('2026-09-01 09:00:00', $deadlines[0]['starts_at']);
        $this->assertSame('2026-09-30 17:00:00', $deadlines[0]['ends_at']);
    }

    public function test_it_ignores_completely_blank_rows_from_the_backoffice_form(): void
    {
        $deadlines = $this->service->normalize(
            '2026-09-01 09:00:00',
            '2026-09-30 17:00:00',
            [[
                'type' => '',
                'label' => '',
                'starts_at' => '',
                'ends_at' => '',
                'description' => '',
            ]],
        );

        $this->assertCount(1, $deadlines);
        $this->assertSame(ContestDeadlineType::Applications->value, $deadlines[0]['type']);
    }

    public function test_it_rejects_overlapping_processing_phases(): void
    {
        try {
            $this->service->normalize(
                '2026-09-01 09:00:00',
                '2026-09-30 17:00:00',
                [[
                    'type' => ContestDeadlineType::Review->value,
                    'label' => 'Análise inicial',
                    'starts_at' => '2026-09-30 17:00:00',
                    'ends_at' => '2026-10-15 17:00:00',
                ]],
            );

            $this->fail('Era esperada uma ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('deadlines', $exception->errors());
        }
    }

    public function test_it_reports_missing_processing_windows_without_breaking_legacy_contests(): void
    {
        $contest = (new Contest)->forceFill([
            'opens_at' => '2026-09-01 09:00:00',
            'closes_at' => '2026-09-30 17:00:00',
        ]);
        $contest->setRelation('deadlines', new Collection([
            (new ContestDeadline)->forceFill([
                'type' => ContestDeadlineType::Corrections->value,
                'label' => 'Aperfeiçoamento',
                'starts_at' => '2026-10-16 09:00:00',
                'ends_at' => '2026-10-25 17:00:00',
            ]),
        ]));

        $readiness = $this->service->readiness($contest);

        $this->assertFalse($readiness['complete']);
        $this->assertSame([
            ContestDeadlineType::Review,
            ContestDeadlineType::Revalidation,
        ], $readiness['missing']);
    }
}
