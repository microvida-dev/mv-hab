<?php

namespace Tests\Unit\Cases;

use App\Data\Cases\CaseChecklistItemData;
use App\Services\Cases\CaseSearchService;
use Tests\TestCase;

class CaseSearchServiceTest extends TestCase
{
    public function test_case_search_returns_contextual_matches_only_for_meaningful_terms(): void
    {
        $service = app(CaseSearchService::class);
        $items = [new CaseChecklistItemData('Programa definido', 'completed', 'Programa municipal associado.')];

        $this->assertSame([], $service->search('p', [], $items, [], [], [], []));

        $results = $service->search('Programa', [], $items, [], [], [], []);

        $this->assertCount(1, $results);
        $this->assertSame('Programa definido', $results[0]->label);
    }
}
