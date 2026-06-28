<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\ChartDatasetService;
use Tests\TestCase;

class ChartDatasetServiceTest extends TestCase
{
    public function test_chart_dataset_service_humanizes_known_statuses_in_portuguese(): void
    {
        $dataset = app(ChartDatasetService::class)->fromKeyedCounts(
            'bar',
            'Candidaturas por estado',
            'Distribuição agregada.',
            ['submitted' => 3, 'under_review' => 2],
        );

        $this->assertSame('Submetida', $dataset['items'][0]['label']);
        $this->assertSame(5, $dataset['total']);
    }
}
