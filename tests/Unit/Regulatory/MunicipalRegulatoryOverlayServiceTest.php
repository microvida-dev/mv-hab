<?php

namespace Tests\Unit\Regulatory;

use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Municipality;
use App\Services\Regulatory\MunicipalRegulatoryOverlayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MunicipalRegulatoryOverlayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipal_overlay_may_strengthen_national_requirements(): void
    {
        $parent = $this->nationalProfile();
        $overlay = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => Municipality::factory(),
            'parent_profile_id' => $parent->id,
            'maximum_effort_rate_percentage' => '35.00',
            'minimum_adult_monthly_income' => '900.00',
            'annual_income_base_limit' => '38000.00',
            'second_person_increment' => '9000.00',
            'additional_person_increment' => '4500.00',
            'sixth_irs_bracket_upper_limit' => '49000.00',
            'minimum_contract_months' => 60,
            'standard_contract_months' => 60,
        ]);
        $service = app(MunicipalRegulatoryOverlayService::class);

        $service->assertValid($overlay);
        $effective = $service->effectiveParameters($overlay);

        $this->assertSame('35.00', $effective['maximum_effort_rate_percentage']);
        $this->assertSame('900.00', $effective['minimum_adult_monthly_income']);
        $this->assertSame(60, $effective['minimum_contract_months']);
    }

    public function test_municipal_overlay_cannot_weaken_national_requirements(): void
    {
        $parent = $this->nationalProfile();
        $overlay = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => Municipality::factory(),
            'parent_profile_id' => $parent->id,
            'maximum_effort_rate_percentage' => '45.00',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não pode aumentar taxa máxima de esforço');

        app(MunicipalRegulatoryOverlayService::class)->assertValid($overlay);
    }

    public function test_municipal_overlay_cannot_increase_national_fiscal_ceiling(): void
    {
        $parent = $this->nationalProfile();
        $overlay = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => Municipality::factory(),
            'parent_profile_id' => $parent->id,
            'sixth_irs_bracket_upper_limit' => '50000.01',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('limite superior do 6.º escalão do IRS');

        app(MunicipalRegulatoryOverlayService::class)->assertValid($overlay);
    }

    private function nationalProfile(): AffordableRentRegulatoryProfile
    {
        return AffordableRentRegulatoryProfile::factory()->create([
            'maximum_effort_rate_percentage' => '40.00',
            'minimum_adult_monthly_income' => '800.00',
            'annual_income_base_limit' => '40000.00',
            'second_person_increment' => '10000.00',
            'additional_person_increment' => '5000.00',
            'sixth_irs_bracket_upper_limit' => '50000.00',
            'minimum_contract_months' => 36,
            'standard_contract_months' => 36,
        ]);
    }
}
