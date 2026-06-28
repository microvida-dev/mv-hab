<?php

namespace Tests\Unit\Cases;

use App\Models\Contest;
use App\Services\Cases\CaseChecklistAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseChecklistAggregatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_contest_checklist_contains_expected_items(): void
    {
        $contest = Contest::factory()->open()->create();

        $items = app(CaseChecklistAggregator::class)->forCase('contest', $contest);

        $this->assertTrue(collect($items)->contains(fn ($item): bool => $item->label === 'Programa definido'));
        $this->assertTrue(collect($items)->contains(fn ($item): bool => $item->label === 'Prazos definidos'));
    }
}
