<?php

namespace Tests\Feature\Concurrency;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\FeatureKey;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewPublication;
use App\Models\AuditLog;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Administrative\CandidateCorrectionWorkspaceService;
use App\Services\Administrative\CorrectionDifferentialResolver;
use App\Services\Administrative\CorrectionResolutionService;
use App\Services\Administrative\CorrectionSubmissionService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\CreatesPublishedCorrectionRequests;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53FCorrectionConcurrencyTest extends TestCase
{
    use CreatesPublishedCorrectionRequests;
    use DatabaseMigrations;
    use InteractsWithMunicipalFeatures;

    public function runDatabaseMigrations(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();

        $this->beforeApplicationDestroyed(function (): void {
            $this->artisan('migrate:fresh');
            $this->app[Kernel::class]->setArtisan(null);
            RefreshDatabaseState::$migrated = false;
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $this->seed(SystemAccessSeeder::class);
        Storage::fake('local');
        Queue::fake();
    }

    public function test_decision_seal_publication_projection_and_notification_are_unique_under_real_concurrency(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->assertSame(
                'sqlite',
                DB::getDriverName(),
                'A execução concorrente real é validada no gate MySQL/MariaDB.',
            );

            return;
        }

        $this->assertContains(DB::getDriverName(), ['mysql', 'mariadb']);
        [$request, $response, $operator] = $this->submittedRequest();
        $starts = $this->runConcurrently('start', [
            'actor_id' => $operator->id,
            'correction_request_id' => $request->id,
        ]);
        $this->assertSame(
            $starts[0]['result']['correction_request_id'],
            $starts[1]['result']['correction_request_id'],
        );
        $this->assertSame(1, AuditLog::query()
            ->where('action', 'correction_revalidation_started')
            ->where('auditable_id', $request->id)
            ->count());

        $item = app(CorrectionDifferentialResolver::class)
            ->resolve($request->refresh())
            ->reviewableItems()[0];

        $decisions = $this->runConcurrently('decide', [
            'actor_id' => $operator->id,
            'correction_request_id' => $request->id,
            'correction_response_id' => $response->id,
            'result' => CorrectionResponseReviewResult::Accepted->value,
            'review_notes' => 'Decisão concorrente idempotente 53F.',
            'source_fingerprint' => $item->sourceFingerprint,
        ]);
        $this->assertSame(
            $decisions[0]['result']['correction_response_id'],
            $decisions[1]['result']['correction_response_id'],
        );
        $this->assertSame(1, AuditLog::query()
            ->where('action', 'correction_item_reviewed')
            ->where('auditable_id', $response->id)
            ->count());

        $resolution = app(CorrectionResolutionService::class);
        $resolutionPreview = $resolution->preview(
            $request->refresh(),
            $operator,
            'Selagem concorrente final 53F.',
        );
        $seals = $this->runConcurrently('seal', [
            'actor_id' => $operator->id,
            'correction_request_id' => $request->id,
            'reason' => $resolutionPreview['reason'],
            'preview_token' => $resolutionPreview['token'],
        ]);
        $this->assertSame(
            $seals[0]['result']['application_review_batch_id'],
            $seals[1]['result']['application_review_batch_id'],
        );
        $batch = ApplicationReviewBatch::query()
            ->where('correction_request_id', $request->id)
            ->sole();
        $this->assertSame(1, ApplicationReviewBatch::query()
            ->where('correction_request_id', $request->id)
            ->count());
        $this->assertSame(1, AuditLog::query()
            ->where('action', 'correction_revalidation_sealed')
            ->where('auditable_id', $batch->id)
            ->count());

        $publicationService = app(ApplicationReviewPublicationService::class);
        $publicationReason = 'Publicação concorrente final 53F.';
        $publicationPreview = $publicationService->preview(
            $batch->load(['contest.program', 'items']),
            $operator,
            $publicationReason,
        );
        $publications = $this->runConcurrently('publish', [
            'actor_id' => $operator->id,
            'application_review_batch_id' => $batch->id,
            'reason' => $publicationReason,
            'preview_token' => $publicationPreview['token'],
        ]);
        $this->assertSame(
            $publications[0]['result']['application_review_publication_id'],
            $publications[1]['result']['application_review_publication_id'],
        );
        $publication = ApplicationReviewPublication::query()
            ->where('application_review_batch_id', $batch->id)
            ->sole();
        $result = $publication->results()->sole();
        $projected = $request->refresh();

        $this->assertSame(1, $publication->results()->count());
        $this->assertSame(1, OfficialNotification::query()
            ->where('notifiable_type', $publication->getMorphClass())
            ->where('notifiable_id', $publication->id)
            ->count());
        $this->assertSame($result->id, $projected->revalidation_publication_result_id);
        $this->assertSame(CorrectionRequestStatus::Resolved, $projected->status);
        $this->assertSame(
            CorrectionRevalidationAggregateResult::Accepted,
            $projected->revalidation_result,
        );
        $this->assertSame(
            AdministrativeProcessStatus::EligibilityReview,
            $projected->administrativeProcess()->firstOrFail()->status,
        );

        foreach ([
            'correction_revalidation_published',
            'correction_request_resolved',
            'correction_revalidation_projected',
        ] as $action) {
            $this->assertSame(1, AuditLog::query()
                ->where('action', $action)
                ->whereIn('auditable_id', [$request->id, $result->id])
                ->count(), $action);
        }
    }

    /** @return array{CorrectionRequest, CorrectionResponse, User} */
    private function submittedRequest(): array
    {
        $municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $operator = User::factory()->create(['status' => 'active']);
        $operator->assignRole('administrator');
        $request = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $operator,
            status: CorrectionRequestStatus::Open,
            completedItems: 0,
            totalItems: 1,
            deadline: now()->addWeek(),
        );
        $request->administrativeProcess()->update([
            'assigned_to' => $operator->id,
        ]);
        $candidate = $request->candidate()->firstOrFail();
        $response = app(CandidateCorrectionWorkspaceService::class)->save(
            request: $request->refresh(),
            item: $request->items()->firstOrFail(),
            data: [],
            file: UploadedFile::fake()->create(
                'concorrencia-53f.pdf',
                32,
                'application/pdf',
            ),
            candidate: $candidate,
        );
        app(CorrectionSubmissionService::class)->submit(
            $request->refresh(),
            $candidate,
        );

        return [$request->refresh(), $response->refresh(), $operator->refresh()];
    }

    /**
     * @param  array<string, int|string>  $payload
     * @return list<array{success:bool, result:array<string, int>}>
     */
    private function runConcurrently(string $operation, array $payload): array
    {
        $directory = sys_get_temp_dir().'/mvhab-s53f-'.Str::uuid();

        if (! mkdir($directory, 0700) && ! is_dir($directory)) {
            throw new RuntimeException('Não foi possível criar a diretoria temporária de concorrência.');
        }

        $payloadPath = $directory.'/payload.json';
        $barrierPath = $directory.'/barrier';
        file_put_contents(
            $payloadPath,
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
        $processes = [];

        try {
            foreach ([1, 2] as $worker) {
                $readyPath = $directory.'/ready-'.$worker;
                $outputPath = $directory.'/output-'.$worker.'.json';
                $pipes = [];
                $process = proc_open(
                    [
                        PHP_BINARY,
                        base_path('tests/Support/Concurrency/sprint53f-correction-worker.php'),
                        $operation,
                        $payloadPath,
                        $barrierPath,
                        $readyPath,
                        $outputPath,
                    ],
                    [
                        0 => ['pipe', 'r'],
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w'],
                    ],
                    $pipes,
                    base_path(),
                    $this->workerEnvironment(),
                );

                if (! is_resource($process)) {
                    throw new RuntimeException('Não foi possível iniciar um worker de concorrência.');
                }

                fclose($pipes[0]);
                $processes[] = compact(
                    'process',
                    'pipes',
                    'readyPath',
                    'outputPath',
                );
            }

            $deadline = microtime(true) + 20;

            while (
                collect($processes)->contains(
                    static fn (array $worker): bool => ! is_file($worker['readyPath']),
                )
            ) {
                if (microtime(true) >= $deadline) {
                    throw new RuntimeException('Os workers não atingiram a barreira concorrente.');
                }

                usleep(10_000);
            }

            touch($barrierPath);
            $results = [];

            foreach ($processes as $worker) {
                $stdout = stream_get_contents($worker['pipes'][1]);
                $stderr = stream_get_contents($worker['pipes'][2]);
                fclose($worker['pipes'][1]);
                fclose($worker['pipes'][2]);
                $exitCode = proc_close($worker['process']);
                $decodedOutput = is_file($worker['outputPath'])
                    ? json_decode(
                        (string) file_get_contents($worker['outputPath']),
                        true,
                        512,
                        JSON_THROW_ON_ERROR,
                    )
                    : null;

                $this->assertSame(0, $exitCode, trim($stdout."\n".$stderr));
                $results[] = $this->validatedWorkerOutput($decodedOutput);
            }

            return $results;
        } finally {
            foreach ($processes as $worker) {
                if (is_resource($worker['process'])) {
                    proc_terminate($worker['process']);
                }

                foreach ($worker['pipes'] as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
            }

            foreach (glob($directory.'/*') ?: [] as $file) {
                @unlink($file);
            }

            @rmdir($directory);
        }
    }

    /** @return array{success:bool, result:array<string, int>} */
    private function validatedWorkerOutput(mixed $output): array
    {
        if (
            ! is_array($output)
            || ($output['success'] ?? null) !== true
            || ! is_array($output['result'] ?? null)
        ) {
            $encoded = json_encode($output);

            throw new RuntimeException(
                'O worker de concorrência falhou: '.(
                    is_string($encoded) ? $encoded : 'resposta inválida'
                ),
            );
        }

        $result = [];

        foreach ($output['result'] as $key => $value) {
            if (! is_string($key) || ! is_int($value)) {
                throw new RuntimeException(
                    'O worker de concorrência devolveu um resultado inválido.',
                );
            }

            $result[$key] = $value;
        }

        return [
            'success' => true,
            'result' => $result,
        ];
    }

    /** @return array<string, string> */
    private function workerEnvironment(): array
    {
        $connection = config('database.default');
        $database = config('database.connections.'.$connection);

        if (! is_string($connection) || ! is_array($database)) {
            throw new RuntimeException('A ligação da base de dados de concorrência é inválida.');
        }

        $environment = getenv();
        $environment['APP_ENV'] = 'testing';
        $environment['APP_KEY'] = (string) config('app.key');
        $environment['DB_URL'] = '';
        $environment['DB_CONNECTION'] = $connection;
        $environment['DB_HOST'] = (string) ($database['host'] ?? '127.0.0.1');
        $environment['DB_PORT'] = (string) ($database['port'] ?? '3306');
        $environment['DB_SOCKET'] = (string) ($database['unix_socket'] ?? '');
        $environment['DB_DATABASE'] = (string) ($database['database'] ?? '');
        $environment['DB_USERNAME'] = (string) ($database['username'] ?? '');
        $environment['DB_PASSWORD'] = (string) ($database['password'] ?? '');
        $environment['QUEUE_CONNECTION'] = 'sync';
        $environment['MAIL_MAILER'] = 'array';
        $environment['CACHE_STORE'] = 'array';
        $environment['SESSION_DRIVER'] = 'array';
        $environment['MVHAB_REGULATORY_DEMO_MODE'] = 'true';
        $environment['MVHAB_MUNICIPAL_APPLICATION_DEMO'] = 'false';
        $environment['MVHAB_PROCEDURAL_EMAIL_SIMULATE'] = 'false';

        return $environment;
    }
}
