<?php

namespace Tests\Unit\Security;

use App\Services\Security\PasswordPolicyService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestPasswords;
use Tests\TestCase;

class PasswordPolicyServiceTest extends TestCase
{
    public function test_policy_configuration_is_centralized_and_complete(): void
    {
        $rules = app(PasswordPolicyService::class)->rule()->appliedRules();

        $this->assertSame(PasswordPolicyService::MIN_LENGTH, $rules['min']);
        $this->assertSame(PasswordPolicyService::MAX_LENGTH, $rules['max']);
        $this->assertTrue($rules['letters']);
        $this->assertTrue($rules['mixedCase']);
        $this->assertTrue($rules['numbers']);
        $this->assertTrue($rules['symbols']);
        $this->assertFalse($rules['uncompromised']);
    }

    public function test_default_password_rule_uses_the_central_policy(): void
    {
        $rules = Password::default()->appliedRules();

        $this->assertSame(PasswordPolicyService::MIN_LENGTH, $rules['min']);
        $this->assertSame(PasswordPolicyService::MAX_LENGTH, $rules['max']);
        $this->assertTrue($rules['mixedCase']);
        $this->assertTrue($rules['numbers']);
        $this->assertTrue($rules['symbols']);
    }

    #[DataProvider('invalidPasswords')]
    public function test_invalid_passwords_are_rejected(string $password): void
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => [app(PasswordPolicyService::class)->rule()]],
        );

        $this->assertTrue($validator->fails());
    }

    public function test_valid_password_is_accepted(): void
    {
        $validator = Validator::make(
            ['password' => TestPasswords::VALID],
            ['password' => [app(PasswordPolicyService::class)->rule()]],
        );

        $this->assertTrue($validator->passes());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPasswords(): array
    {
        return [
            'too short' => ['Aa1!curta'],
            'without uppercase' => ['mvhab!teste2026seguro'],
            'without lowercase' => ['MVHAB!TESTE2026SEGURO'],
            'without number' => ['MVHab!TesteSeguroSemNumero'],
            'without symbol' => ['MVHabTeste2026Seguro'],
            'above maximum length' => ['Aa1!'.str_repeat('x', 125)],
        ];
    }
}
