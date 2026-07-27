<?php

namespace Tests\Feature\Seeders;

use App\Support\Demo\MunicipalApplicationDemoContext;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\MunicipalApplicationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class MunicipalApplicationDemoSeederGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';

        config()->set('mvhab.regulatory_demo_mode', false);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            false,
        );
        config()->set(
            'mvhab.municipal_application_demo.reference_date',
            null,
        );
        config()->set(
            'mvhab.municipal_application_demo.user_password',
            null,
        );
    }

    public function test_seeder_is_rejected_when_both_demo_modes_are_disabled(): void
    {
        $this->assertSeederRejected(
            'O seeder municipal exige '
            .'MVHAB_REGULATORY_DEMO_MODE=true.',
        );
    }

    public function test_seeder_is_rejected_when_only_regulatory_demo_mode_is_enabled(): void
    {
        config()->set('mvhab.regulatory_demo_mode', true);

        $this->assertSeederRejected(
            'O seeder municipal exige '
            .'MVHAB_MUNICIPAL_APPLICATION_DEMO=true.',
        );
    }

    public function test_seeder_is_rejected_when_only_municipal_demo_mode_is_enabled(): void
    {
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );

        $this->assertSeederRejected(
            'O seeder municipal exige '
            .'MVHAB_REGULATORY_DEMO_MODE=true.',
        );
    }

    public function test_seeder_is_allowed_when_both_demo_modes_are_enabled(): void
    {
        $this->enableDemoModes();

        $this->seed(MunicipalApplicationDemoSeeder::class);

        $context = app(MunicipalApplicationDemoContext::class);

        $this->assertTrue($context->enabled());
        $this->assertTrue($context->regulatoryDemoModeEnabled());
    }

    public function test_seeder_is_rejected_in_production_even_when_demo_modes_are_enabled(): void
    {
        $this->enableDemoModes();

        $originalEnvironment = $this->app->environment();
        $this->app['env'] = 'production';

        try {
            app(MunicipalApplicationDemoContext::class)
                ->assertSeederAllowed();

            $this->fail(
                'O seeder municipal não foi recusado em produção.',
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'O seeder municipal só pode ser executado '
                .'em ambiente demo, local ou testing.',
                $exception->getMessage(),
            );
        } finally {
            $this->app['env'] = $originalEnvironment;
        }
    }

    public function test_seeder_is_rejected_when_demo_password_is_missing(): void
    {
        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );

        $this->assertSeederRejected(
            'O seeder municipal exige uma password demo '
            .'com pelo menos 12 caracteres.',
        );
    }

    public function test_configured_reference_date_is_respected(): void
    {
        config()->set(
            'mvhab.municipal_application_demo.reference_date',
            '2026-07-27',
        );

        $referenceDate = app(
            MunicipalApplicationDemoContext::class,
        )->referenceDate();

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $referenceDate,
        );
        $this->assertSame(
            '2026-07-27',
            $referenceDate->toDateString(),
        );
        $this->assertSame(
            '00:00:00',
            $referenceDate->format('H:i:s'),
        );
        $this->assertSame(
            'Europe/Lisbon',
            $referenceDate->getTimezone()->getName(),
        );
    }

    public function test_demo_configuration_is_registered_at_the_top_level(): void
    {
        /** @var array<string, mixed> $configuration */
        $configuration = require config_path('mvhab.php');

        $this->assertArrayHasKey(
            'municipal_application_demo',
            $configuration,
        );

        $demoConfiguration = $configuration[
            'municipal_application_demo'
        ];

        $this->assertIsArray($demoConfiguration);
        $this->assertArrayHasKey(
            'enabled',
            $demoConfiguration,
        );
        $this->assertArrayHasKey(
            'reference_date',
            $demoConfiguration,
        );
        $this->assertArrayHasKey(
            'user_password',
            $demoConfiguration,
        );
    }

    private function enableDemoModes(): void
    {
        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );
        config()->set(
            'mvhab.municipal_application_demo.user_password',
            'MVHAB-Demo-2026!',
        );
    }

    private function assertSeederRejected(
        string $expectedMessage,
    ): void {
        try {
            $this->seed(MunicipalApplicationDemoSeeder::class);

            $this->fail(
                'O seeder municipal deveria ter sido recusado.',
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                $expectedMessage,
                $exception->getMessage(),
            );
        }
    }
}
