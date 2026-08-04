<?php

namespace Tests\Unit\Municipalities;

use App\Services\Municipalities\MunicipalityIdentityNormalizer;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MunicipalityIdentityNormalizerTest extends TestCase
{
    public function test_it_normalizes_onboarding_identity_fields(): void
    {
        $data = app(MunicipalityIdentityNormalizer::class)->onboardingData([
            'actor_id' => '7',
            'name' => '  Município   de Alcanena ',
            'code' => ' alcanena ',
            'tax_number' => '506 000 001',
            'contact_email' => ' HABITACAO@ALCANENA.PT ',
            'admin_name' => '  Administrador   Municipal ',
            'admin_email' => ' ADMIN@ALCANENA.PT ',
            'justification' => '  Aprovação institucional para o primeiro onboarding municipal. ',
        ]);

        $this->assertSame(7, $data->actorId);
        $this->assertSame('Município de Alcanena', $data->name);
        $this->assertSame('ALCANENA', $data->code);
        $this->assertSame('506000001', $data->taxNumber);
        $this->assertSame('habitacao@alcanena.pt', $data->contactEmail);
        $this->assertSame('Administrador Municipal', $data->adminName);
        $this->assertSame('admin@alcanena.pt', $data->adminEmail);
    }

    #[DataProvider('invalidTaxNumbers')]
    public function test_it_rejects_invalid_tax_numbers(string $taxNumber): void
    {
        $this->expectException(DomainException::class);

        app(MunicipalityIdentityNormalizer::class)->taxNumber($taxNumber);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidTaxNumbers(): array
    {
        return [
            'empty' => [''],
            'too short' => ['12345678'],
            'invalid checksum' => ['506000002'],
            'letters only' => ['ABCDEFGHI'],
        ];
    }
}
