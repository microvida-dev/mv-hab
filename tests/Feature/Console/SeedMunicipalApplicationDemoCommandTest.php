<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SeedMunicipalApplicationDemo;
use App\Services\Demo\MunicipalApplicationDemoSummaryService;
use Carbon\CarbonImmutable;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Tests\TestCase;

class SeedMunicipalApplicationDemoCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MVHAB-Demo-2026!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';

        config()->set('cache.default', 'array');
        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );
        config()->set(
            'mvhab.municipal_application_demo.reference_date',
            '2026-07-27',
        );
        config()->set(
            'mvhab.municipal_application_demo.user_password',
            self::PASSWORD,
        );
        config()->set('document-ai.enabled', true);

        Cache::flush();
        Storage::fake('local');
        Queue::fake();

        CarbonImmutable::setTestNow(
            CarbonImmutable::create(
                2026,
                7,
                27,
                12,
                0,
                timezone: 'Europe/Lisbon',
            ),
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_command_is_registered_and_creates_verified_scenario(): void
    {
        $this->artisan(
            'mvhab:demo:municipal-application',
            ['--force' => true],
        )
            ->expectsOutputToContain(
                'Cenário municipal demo criado e verificado.',
            )
            ->expectsOutputToContain(
                'Dados fictícios e sem efeitos administrativos.',
            )
            ->assertSuccessful();

        $summary = app(
            MunicipalApplicationDemoSummaryService::class,
        )->verify();

        $this->assertSame(
            'ALCANENA-DEMO',
            data_get($summary, 'municipality.code'),
        );
        $this->assertSame(
            15,
            data_get($summary, 'counts.document_submissions'),
        );
        $this->assertSame(
            16,
            data_get($summary, 'counts.document_versions'),
        );
        $this->assertSame(
            1,
            data_get($summary, 'counts.housing_visits'),
        );
        $this->assertSame(
            2,
            data_get($summary, 'counts.application_reports'),
        );
        $this->assertSame(
            15,
            data_get($summary, 'counts.document_dossier_items'),
        );
        $this->assertSame(
            2,
            data_get($summary, 'counts.municipalities'),
        );
        $this->assertSame(
            2,
            data_get($summary, 'counts.review_batches'),
        );
        $this->assertSame(
            3,
            data_get($summary, 'counts.review_publication_results'),
        );
        $this->assertSame(
            1,
            data_get($summary, 'counts.correction_submission_receipts'),
        );
        $this->assertSame(
            1,
            data_get($summary, 'counts.expired_without_response'),
        );
        $this->assertSame(
            2,
            data_get($summary, 'counts.temporal_exports'),
        );
        $this->assertTrue(
            data_get(
                $summary,
                'program53.municipality_isolation.cross_access_denied',
            ),
        );

        Queue::assertNothingPushed();
    }

    /**
     * @throws JsonException
     */
    public function test_json_output_is_machine_readable_and_never_exposes_password(): void
    {
        $exitCode = Artisan::call(
            'mvhab:demo:municipal-application',
            [
                '--force' => true,
                '--format' => 'json',
            ],
        );

        $this->assertSame(0, $exitCode);

        $output = trim(Artisan::output());

        $this->assertStringNotContainsString(
            self::PASSWORD,
            $output,
        );

        $payload = json_decode(
            $output,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertIsArray($payload);
        $this->assertSame(
            'ALCANENA-DEMO',
            data_get($payload, 'municipality.code'),
        );
        $this->assertSame(
            'submitted',
            data_get($payload, 'application.status'),
        );
        $this->assertCount(
            6,
            data_get($payload, 'accounts', []),
        );
        $this->assertSame(
            'analista-candidaturas-exportacao',
            data_get($payload, 'program53_profile.template_key'),
        );
        $this->assertTrue((bool) data_get(
            $payload,
            'program53_profile.mfa_required',
        ));
        $this->assertFalse((bool) data_get(
            $payload,
            'program53_profile.global_scope',
        ));
        $this->assertContains(
            'reports.export_sensitive',
            data_get($payload, 'program53_profile.denied_operations', []),
        );
        $this->assertSame(
            0,
            data_get(
                $payload,
                'counts.document_ai_analyses',
            ),
        );
    }

    public function test_verify_only_requires_an_existing_complete_scenario(): void
    {
        $this->artisan(
            'mvhab:demo:municipal-application',
            [
                '--verify-only' => true,
                '--format' => 'table',
            ],
        )
            ->expectsOutputToContain(
                'Falha no cenário municipal demo:',
            )
            ->assertFailed();

        $this->assertDatabaseCount('municipalities', 0);
        $this->assertDatabaseCount('applications', 0);
    }

    public function test_command_fails_closed_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan(
            'mvhab:demo:municipal-application',
            ['--force' => true],
        )
            ->expectsOutputToContain(
                'O seeder municipal só pode ser executado',
            )
            ->assertFailed();

        $this->assertDatabaseCount('municipalities', 0);
        $this->assertDatabaseCount('applications', 0);
    }

    public function test_concurrent_execution_is_rejected(): void
    {
        $lock = Cache::lock(
            SeedMunicipalApplicationDemo::LOCK_NAME,
            600,
        );

        $this->assertTrue($lock->get());

        try {
            $this->artisan(
                'mvhab:demo:municipal-application',
                ['--force' => true],
            )
                ->expectsOutputToContain(
                    'Já existe uma execução do cenário municipal '
                    .'demo em curso.',
                )
                ->assertFailed();
        } finally {
            $lock->release();
        }

        $this->assertDatabaseCount('municipalities', 0);
    }

    public function test_command_is_idempotent_and_verify_only_preserves_state(): void
    {
        $this->artisan(
            'mvhab:demo:municipal-application',
            ['--force' => true],
        )->assertSuccessful();

        $service = app(
            MunicipalApplicationDemoSummaryService::class,
        );
        $first = $service->verify();
        unset($first['verified_at']);

        $this->artisan(
            'mvhab:demo:municipal-application',
            ['--force' => true],
        )->assertSuccessful();

        $this->artisan(
            'mvhab:demo:municipal-application',
            ['--verify-only' => true],
        )
            ->expectsOutputToContain(
                'Cenário municipal demo verificado.',
            )
            ->assertSuccessful();

        $second = $service->verify();
        unset($second['verified_at']);

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('municipalities', 2);
        $this->assertDatabaseCount('applications', 3);
        $this->assertDatabaseCount('application_review_batches', 3);
        $this->assertDatabaseCount(
            'application_review_batch_items',
            4,
        );
        $this->assertDatabaseCount(
            'application_review_publications',
            3,
        );
        $this->assertDatabaseCount(
            'application_review_publication_results',
            4,
        );
        $this->assertDatabaseCount(
            'correction_submission_receipts',
            1,
        );
        $this->assertDatabaseCount('report_exports', 2);
        $this->assertDatabaseCount('document_submissions', 15);
        $this->assertDatabaseCount('document_versions', 16);
        $this->assertDatabaseCount('housing_visits', 1);
        $this->assertDatabaseCount('application_reports', 2);
        $this->assertDatabaseCount('document_dossiers', 1);
        $this->assertDatabaseCount('document_dossier_items', 15);
        $this->assertDatabaseHas('users', [
            'email' => 'analista.exportacao.demo@mvhab.local',
            'municipality_id' => data_get($second, 'municipality.id'),
            'status' => 'active',
            'mfa_required' => true,
        ]);

        Queue::assertNothingPushed();
    }

    public function test_command_isolates_the_demo_from_an_installed_global_document_catalogue(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);

        $this->artisan(
            'mvhab:demo:municipal-application',
            ['--force' => true],
        )->assertSuccessful();

        $this->artisan(
            'mvhab:demo:municipal-application',
            ['--verify-only' => true],
        )->assertSuccessful();

        $this->assertDatabaseCount('document_submissions', 15);
        $this->assertDatabaseCount('document_dossier_items', 15);
        $this->assertDatabaseMissing('document_dossier_items', [
            'is_required' => false,
        ]);

        Queue::assertNothingPushed();
    }
}
