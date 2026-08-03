<?php

namespace Tests\Feature\Regulatory;

use App\Models\AuditLog;
use App\Models\Contract;
use Database\Seeders\AffordableRentRegulatoryProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RegulatoryReadOnlyCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rent_limit_audit_fails_closed_when_regime_has_no_installed_profile(): void
    {
        $path = storage_path('framework/testing/empty-paa-rent-audit.json');

        $this->artisan('regulatory:audit-rent-limit-tables', [
            '--regime' => 'paa_legacy_2019',
            '--reference-date' => '2026-07-15',
            '--format' => 'json',
            '--output' => $path,
        ])->assertSuccessful();
        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('configuration_incomplete', $payload['status']);
        $this->assertSame(0, $payload['summary']['profiles']);
        $this->assertNotEmpty($payload['findings']);
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_rent_limit_audit_is_deterministic_and_reports_missing_official_paa_source(): void
    {
        $this->seed(AffordableRentRegulatoryProfileSeeder::class);
        $path = storage_path('framework/testing/paa-rent-audit.json');

        $this->artisan('regulatory:audit-rent-limit-tables', [
            '--regime' => 'paa_legacy_2019',
            '--reference-date' => '2026-07-15',
            '--format' => 'json',
            '--output' => $path,
        ])->assertSuccessful();
        $first = File::get($path);

        $this->artisan('regulatory:audit-rent-limit-tables', [
            '--regime' => 'paa_legacy_2019',
            '--reference-date' => '2026-07-15',
            '--format' => 'json',
            '--output' => $path,
        ])->assertSuccessful();
        $second = File::get($path);
        $payload = json_decode($second, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($first, $second);
        $this->assertSame('configuration_incomplete', $payload['status']);
        $this->assertSame(0, $payload['summary']['configured']);
        $this->assertSame(1, $payload['summary']['incomplete']);
        $this->assertSame('incomplete', $payload['tables'][0]['status']);
        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_legacy_contract_inventory_is_read_only_deterministic_and_has_no_pii(): void
    {
        $contract = Contract::factory()->create();
        $path = storage_path('framework/testing/legacy-contracts.json');
        $before = $contract->fresh()->getAttributes();

        $this->artisan('regulatory:inventory-legacy-contracts', [
            '--format' => 'json',
            '--output' => $path,
        ])->assertSuccessful();
        $first = File::get($path);
        $this->artisan('regulatory:inventory-legacy-contracts', [
            '--format' => 'json',
            '--output' => $path,
        ])->assertSuccessful();
        $second = File::get($path);
        $payload = json_decode($second, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($first, $second);
        $this->assertSame('missing_rent_calculation', $payload['contracts'][0]['classification']);
        $this->assertSame($before, $contract->fresh()->getAttributes());
        $this->assertSame(0, AuditLog::query()->count());
        $this->assertStringNotContainsString('tenant_name', $second);
        $this->assertStringNotContainsString('tenant_tax_number', $second);
        $this->assertStringNotContainsString('tenant_email', $second);
        $this->assertStringNotContainsString('tenant_address', $second);
    }
}
