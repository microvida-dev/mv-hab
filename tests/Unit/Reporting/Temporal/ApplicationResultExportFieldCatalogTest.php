<?php

namespace Tests\Unit\Reporting\Temporal;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationResultExportSensitivity;
use App\Services\Reporting\Temporal\ApplicationResultExportFieldCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApplicationResultExportFieldCatalogTest extends TestCase
{
    #[DataProvider('modes')]
    public function test_catalog_exposes_required_application_fields_for_each_mode(
        ApplicationResultExportMode $mode,
    ): void {
        $fields = collect(app(ApplicationResultExportFieldCatalog::class)->forDataset(
            $mode,
            ApplicationResultExportDataset::Applications,
        ));

        $this->assertContains('application_number', $fields->pluck('code'));
        $this->assertContains('source_fingerprint', $fields->pluck('code'));
        $this->assertNotContains('candidate_name', $fields->pluck('code'));
    }

    public function test_sensitive_fields_require_explicit_sensitive_projection(): void
    {
        $catalog = app(ApplicationResultExportFieldCatalog::class);

        $normal = collect($catalog->forDataset(
            ApplicationResultExportMode::CurrentState,
            ApplicationResultExportDataset::Applications,
        ));
        $sensitive = collect($catalog->forDataset(
            ApplicationResultExportMode::CurrentState,
            ApplicationResultExportDataset::Applications,
            includeSensitive: true,
        ));

        $this->assertNotContains('candidate_name', $normal->pluck('code'));
        $this->assertContains('candidate_name', $sensitive->pluck('code'));
        $this->assertSame(
            ApplicationResultExportSensitivity::Personal,
            $catalog->find('candidate_name')?->sensitivity,
        );
    }

    public function test_change_values_are_present_and_redacted_by_compared_field(): void
    {
        $fields = collect(app(ApplicationResultExportFieldCatalog::class)->forDataset(
            ApplicationResultExportMode::DeltaBetweenBatches,
            ApplicationResultExportDataset::Changes,
        ));

        $this->assertContains('before_value', $fields->pluck('code'));
        $this->assertContains('after_value', $fields->pluck('code'));
        $this->assertSame(
            ApplicationResultExportSensitivity::Operational,
            $fields->firstWhere('code', 'before_value')?->sensitivity,
        );
    }

    /** @return iterable<string, array{ApplicationResultExportMode}> */
    public static function modes(): iterable
    {
        foreach (ApplicationResultExportMode::cases() as $mode) {
            yield $mode->value => [$mode];
        }
    }
}
