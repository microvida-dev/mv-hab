<?php

namespace Tests\Feature\Concurrency;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\FeatureKey;
use App\Enums\ReportExportStatus;
use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\Contest;
use App\Models\Program;
use App\Models\User;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class TemporalApplicationResultExportConcurrencyTest extends TestCase
{
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

        $this->seed([SystemAccessSeeder::class, ReportDefinitionSeeder::class]);
        Queue::fake();
    }

    public function test_two_independent_workers_publish_one_final_package(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->assertSame(
                'sqlite',
                DB::getDriverName(),
                'A corrida real de workers é executada no gate MySQL/MariaDB.',
            );

            return;
        }

        $this->assertContains(DB::getDriverName(), ['mysql', 'mariadb']);
        $municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
            FeatureKey::ApplicationExport,
        );
        $program = Program::factory()->create([
            'municipality_id' => $municipality->getKey(),
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->getKey(),
        ]);
        Application::factory()->submitted()->count(25)->create([
            'program_id' => $program->getKey(),
            'contest_id' => $contest->getKey(),
        ]);
        $actor = User::factory()->create([
            'municipality_id' => $municipality->getKey(),
            'status' => 'active',
        ]);
        $actor->assignRole('administrator');
        $export = app(TemporalApplicationResultExportService::class)->request(
            $actor,
            [
                'contest_id' => $contest->getKey(),
                'mode' => ApplicationResultExportMode::CurrentState->value,
                'formats' => array_map(
                    static fn (ApplicationResultExportFormat $format): string => $format->value,
                    ApplicationResultExportFormat::cases(),
                ),
                'datasets' => [ApplicationResultExportDataset::Applications->value],
                'csv_delimiter' => 'semicolon',
                'csv_bom' => true,
                'include_sensitive' => false,
                'include_document_files' => false,
                'changed_documents_only' => false,
                'include_unchanged' => false,
                'idempotency_token' => (string) Str::uuid(),
            ],
        );
        if ($export->created_at === null) {
            throw new RuntimeException('A exportação concorrente não possui data de criação.');
        }
        $publishedDirectory = 'reports/'.$export->created_at->format('Y/m').'/'.$export->public_id;

        try {
            $results = $this->runConcurrently([
                'report_export_id' => (int) $export->getKey(),
            ]);

            $this->assertSame(
                (int) $export->getKey(),
                $results[0]['result']['report_export_id'],
            );
            $this->assertSame(
                (int) $export->getKey(),
                $results[1]['result']['report_export_id'],
            );

            $export->refresh();
            $this->assertSame(ReportExportStatus::Completed, $export->status);
            $this->assertSame(100, $export->progress);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $export->source_fingerprint);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $export->manifest_sha256);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $export->package_sha256);
            Storage::disk('local')->assertExists((string) $export->file_path);
            $this->assertSame(
                $export->package_sha256,
                hash_file('sha256', Storage::disk('local')->path((string) $export->file_path)),
            );
            $this->assertCount(
                1,
                Storage::disk('local')->allFiles(dirname((string) $export->file_path)),
            );
            $this->assertSame(
                [],
                Storage::disk('local')->allFiles('report-exports/temporal/'.$export->public_id),
            );
            foreach ([
                'application_result_export_started',
                'application_result_export_snapshot_created',
                'application_result_export_completed',
            ] as $eventCode) {
                $this->assertSame(1, AuditEvent::query()
                    ->where('event_code', $eventCode)
                    ->where('auditable_id', $export->getKey())
                    ->count());
            }
        } finally {
            Storage::disk('local')->deleteDirectory($publishedDirectory);
            Storage::disk('local')->deleteDirectory('report-exports/temporal/'.$export->public_id);
        }
    }

    /**
     * @param  array<string, int>  $payload
     * @return list<array{success:bool, result:array<string, int>}>
     */
    private function runConcurrently(array $payload): array
    {
        $directory = sys_get_temp_dir().'/mvhab-s53g-'.Str::uuid();

        if (! mkdir($directory, 0700) && ! is_dir($directory)) {
            throw new RuntimeException('Não foi possível criar a diretoria temporária de concorrência.');
        }

        $payloadPath = $directory.'/payload.json';
        $barrierPath = $directory.'/barrier';
        file_put_contents($payloadPath, json_encode($payload, JSON_THROW_ON_ERROR));
        $processes = [];

        try {
            foreach ([1, 2] as $worker) {
                $readyPath = $directory.'/ready-'.$worker;
                $outputPath = $directory.'/output-'.$worker.'.json';
                $pipes = [];
                $process = proc_open(
                    [
                        PHP_BINARY,
                        base_path('tests/Support/Concurrency/temporal-application-result-export-worker.php'),
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

            $deadline = microtime(true) + 30;

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
        $environment['FILESYSTEM_DISK'] = 'local';
        $environment['MVHAB_REGULATORY_DEMO_MODE'] = 'true';
        $environment['MVHAB_MUNICIPAL_APPLICATION_DEMO'] = 'false';
        $environment['MVHAB_PROCEDURAL_EMAIL_SIMULATE'] = 'false';

        return $environment;
    }
}
