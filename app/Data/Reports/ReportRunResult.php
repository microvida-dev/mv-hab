<?php

declare(strict_types=1);

namespace App\Data\Reports;

use App\Models\ReportRun;

final readonly class ReportRunResult
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ReportRun $reportRun,
        public array $rows = [],
        public array $metadata = [],
    ) {}

    /**
     * Compatibilidade temporária com consumidores antigos.
     *
     * @return array{
     *     reportRun: ReportRun,
     *     rows: array<int, array<string, mixed>>,
     *     metadata: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'reportRun' => $this->reportRun,
            'rows' => $this->rows,
            'metadata' => $this->metadata,
        ];
    }
}
