<?php

namespace Tests\Unit\Regulatory;

use App\Enums\RentLimitConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Services\Regulatory\RentLimits\RentLimitProviderRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentLimitProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_rsaa_profile_never_falls_back_to_paa_values(): void
    {
        $profile = AffordableRentRegulatoryProfile::factory()->rsaaIncomplete()->create();
        $provider = app(RentLimitProviderRegistry::class)->forProfile($profile);
        $result = $provider->limitsFor(
            $profile,
            null,
            new CarbonImmutable('2026-09-01', 'Europe/Lisbon'),
        );

        $this->assertSame(RentLimitConfigurationStatus::Incomplete, $result->status);
        $this->assertNull($result->minimumRent);
        $this->assertNull($result->maximumRent);
        $this->assertStringContainsString('RSAA', (string) $result->message);
    }
}
