<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HousingPreferenceInvalidationWriterTest extends TestCase
{
    #[DataProvider('writers')]
    public function test_relevant_writers_dispatch_the_domain_event(
        string $relativePath,
    ): void {
        $contents = file_get_contents(base_path($relativePath));

        $this->assertIsString($contents);
        $this->assertStringContainsString(
            'HousingPreferenceInputsChanged::dispatch',
            $contents,
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function writers(): array
    {
        return [
            'candidate registration' => [
                'app/Services/Candidate/AdhesionRegistrationService.php',
            ],
            'candidate household' => [
                'app/Services/Candidate/HouseholdService.php',
            ],
            'candidate members' => [
                'app/Services/Candidate/HouseholdMemberService.php',
            ],
            'candidate income' => [
                'app/Services/Candidate/IncomeService.php',
            ],
            'candidate housing situation' => [
                'app/Services/Candidate/HousingSituationService.php',
            ],
            'backoffice household' => [
                'app/Http/Controllers/HouseholdController.php',
            ],
            'correction response' => [
                'app/Services/Administrative/CorrectionResponseService.php',
            ],
            'registration renewal' => [
                'app/Services/Simulator/RegistrationRenewalService.php',
            ],
            'annual update' => [
                'app/Services/Finance/AnnualDocumentUpdateService.php',
            ],
        ];
    }
}
