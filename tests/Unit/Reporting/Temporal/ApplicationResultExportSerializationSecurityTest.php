<?php

namespace Tests\Unit\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Services\Reporting\Temporal\ApplicationResultExportPathGuard;
use App\Services\Reporting\Temporal\SpreadsheetCellSanitizer;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ApplicationResultExportSerializationSecurityTest extends TestCase
{
    #[DataProvider('formulaValues')]
    public function test_tabular_values_are_neutralized_without_changing_regular_values(
        string $input,
        string $expected,
    ): void {
        $this->assertSame(
            $expected,
            app(SpreadsheetCellSanitizer::class)->value($input),
        );
    }

    public function test_package_path_guard_rejects_traversal_and_absolute_paths(): void
    {
        $guard = app(ApplicationResultExportPathGuard::class);

        foreach (['../secret', 'safe/../../secret', '/absolute', 'C:/windows', 'safe\\file'] as $path) {
            try {
                $guard->assertRelative($path);
                $this->fail("O path {$path} deveria ter sido recusado.");
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_sensitive_options_require_explicit_confirmation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ApplicationResultExportPackageOptionsData(
            exportPublicId: 'export-id',
            formats: [ApplicationResultExportFormat::Json],
            datasets: [ApplicationResultExportDataset::Applications],
            generatedAt: CarbonImmutable::parse('2026-08-01', 'UTC'),
            expiresAt: CarbonImmutable::parse('2026-08-08', 'UTC'),
            includeSensitive: true,
            sensitiveConfirmed: false,
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function formulaValues(): iterable
    {
        yield 'equals' => ['=2+2', "'=2+2"];
        yield 'plus' => ['+SUM(A1:A2)', "'+SUM(A1:A2)"];
        yield 'minus' => ['-1+2', "'-1+2"];
        yield 'at' => ['@cmd', "'@cmd"];
        yield 'regular' => ['CAND-0001', 'CAND-0001'];
    }
}
