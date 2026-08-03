<?php

namespace Tests\Unit\Security;

use App\Services\Security\Program53RateLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use LogicException;
use Tests\TestCase;

class Program53RateLimitServiceTest extends TestCase
{
    public function test_keys_are_hashed_deterministic_and_isolate_user_and_municipality_dimensions(): void
    {
        $service = app(Program53RateLimitService::class);
        $firstUser = $service->key(101, 30, 'export_request:normal', 'resource-1', 'user');
        $sameUser = $service->key(101, 30, 'export_request:normal', 'resource-1', 'user');
        $secondUser = $service->key(102, 30, 'export_request:normal', 'resource-1', 'user');
        $firstMunicipality = $service->key(101, 30, 'export_request:normal', 'resource-1', 'municipality');
        $sameMunicipality = $service->key(102, 30, 'export_request:normal', 'resource-1', 'municipality');
        $sameMunicipalityOtherResource = $service->key(102, 30, 'export_request:normal', 'resource-2', 'municipality');
        $secondMunicipality = $service->key(101, 31, 'export_request:normal', 'resource-1', 'municipality');

        $this->assertSame($firstUser, $sameUser);
        $this->assertNotSame($firstUser, $secondUser);
        $this->assertSame($firstMunicipality, $sameMunicipality);
        $this->assertSame($firstMunicipality, $sameMunicipalityOtherResource);
        $this->assertNotSame($firstMunicipality, $secondMunicipality);
        $this->assertMatchesRegularExpression('/\Aprogram53:[a-f0-9]{64}\z/', $firstUser);
        $this->assertStringNotContainsString('resource-1', $firstUser);
    }

    public function test_sensitive_export_limits_are_stricter_than_normal_limits(): void
    {
        $service = app(Program53RateLimitService::class);

        foreach ([
            Program53RateLimitService::EXPORT_PREVIEW,
            Program53RateLimitService::EXPORT_REQUEST,
            Program53RateLimitService::EXPORT_DOWNLOAD,
        ] as $operation) {
            $normal = $service->configuration($operation);
            $sensitive = $service->configuration($operation, 'sensitive');

            $this->assertLessThan(
                $normal['user']['max_attempts'],
                $sensitive['user']['max_attempts'],
            );
            $this->assertLessThan(
                $normal['municipality']['max_attempts'],
                $sensitive['municipality']['max_attempts'],
            );
        }
    }

    public function test_sensitive_profile_is_derived_from_request_intent(): void
    {
        $service = app(Program53RateLimitService::class);
        $normal = Request::create('/backoffice/reports/temporal-exports', 'POST');
        $sensitive = Request::create('/backoffice/reports/temporal-exports', 'POST', [
            'include_sensitive' => '1',
        ]);

        $this->assertFalse($service->usesSensitiveProfile(
            $normal,
            Program53RateLimitService::EXPORT_REQUEST,
        ));
        $this->assertTrue($service->usesSensitiveProfile(
            $sensitive,
            Program53RateLimitService::EXPORT_REQUEST,
        ));
        $this->assertFalse($service->usesSensitiveProfile(
            $sensitive,
            Program53RateLimitService::BATCH_SEAL,
        ));
    }

    public function test_invalid_or_incomplete_configuration_fails_closed(): void
    {
        Config::set('mvhab.rate_limits.program53.export_request.normal.user.max_attempts', 0);

        $this->expectException(LogicException::class);

        app(Program53RateLimitService::class)->configuration(
            Program53RateLimitService::EXPORT_REQUEST,
        );
    }

    public function test_unknown_dimension_is_rejected(): void
    {
        $this->expectException(LogicException::class);

        app(Program53RateLimitService::class)->key(
            1,
            1,
            Program53RateLimitService::EXPORT_REQUEST,
            null,
            'unknown',
        );
    }
}
