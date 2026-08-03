<?php

namespace Tests\Feature\Concurrency;

use App\Models\AccessChangeEvent;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class Program53RoleTemplateConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

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
    }

    public function test_two_concurrent_applications_create_one_template_role(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->assertSame(
                'sqlite',
                DB::getDriverName(),
                'A corrida real de aplicação do template é executada no gate MySQL/MariaDB.',
            );

            return;
        }

        $this->assertContains(DB::getDriverName(), ['mysql', 'mariadb']);
        $municipality = Municipality::factory()->create();
        $administrator = User::factory()->create([
            'municipality_id' => $municipality->getKey(),
            'status' => 'active',
        ]);
        $administrator->assignRole('administrator');
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');
        $results = $this->runConcurrently([
            'actor_id' => (int) $administrator->getKey(),
        ]);

        $this->assertSame(
            $results[0]['result']['role_id'],
            $results[1]['result']['role_id'],
        );
        $role = Role::query()
            ->where('municipality_id', $municipality->getKey())
            ->where('template_key', $template['key'])
            ->sole();
        $this->assertSame(
            $results[0]['result']['role_id'],
            (int) $role->getKey(),
        );
        $this->assertSame($template['version'], $role->template_version);
        $this->assertSame(
            $template['fingerprint'],
            $role->template_fingerprint,
        );
        $this->assertSame(
            count($template['permissions']),
            DB::table('permission_role')
                ->where('role_id', $role->getKey())
                ->count(),
        );
        $this->assertSame(
            count($template['permissions']),
            DB::table('permission_role')
                ->where('role_id', $role->getKey())
                ->distinct()
                ->count('permission_id'),
        );
        $this->assertSame(1, AccessChangeEvent::query()
            ->where('event_code', 'municipal_role_template_created')
            ->where('role_id', $role->getKey())
            ->count());
    }

    /**
     * @param  array<string, int>  $payload
     * @return list<array{success: bool, result: array<string, int>}>
     */
    private function runConcurrently(array $payload): array
    {
        $directory = sys_get_temp_dir().'/mvhab-s53h-'.Str::uuid();

        if (! mkdir($directory, 0700) && ! is_dir($directory)) {
            throw new RuntimeException(
                'Não foi possível criar a diretoria temporária de concorrência.',
            );
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
                        base_path(
                            'tests/Support/Concurrency/program53-role-template-worker.php',
                        ),
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
                    throw new RuntimeException(
                        'Não foi possível iniciar um worker de concorrência.',
                    );
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

            while (collect($processes)->contains(
                static fn (array $worker): bool => ! is_file(
                    $worker['readyPath'],
                ),
            )) {
                if (microtime(true) >= $deadline) {
                    throw new RuntimeException(
                        'Os workers não atingiram a barreira concorrente.',
                    );
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
                $decoded = is_file($worker['outputPath'])
                    ? json_decode(
                        (string) file_get_contents($worker['outputPath']),
                        true,
                        512,
                        JSON_THROW_ON_ERROR,
                    )
                    : null;

                $this->assertSame(
                    0,
                    $exitCode,
                    trim($stdout."\n".$stderr),
                );
                $results[] = $this->validatedWorkerOutput($decoded);
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

    /** @return array{success: bool, result: array<string, int>} */
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

        $roleId = $output['result']['role_id'] ?? null;

        if (! is_int($roleId)) {
            throw new RuntimeException(
                'O worker de concorrência devolveu uma role inválida.',
            );
        }

        return [
            'success' => true,
            'result' => ['role_id' => $roleId],
        ];
    }

    /** @return array<string, string> */
    private function workerEnvironment(): array
    {
        $connection = config('database.default');
        $database = config('database.connections.'.$connection);

        if (! is_string($connection) || ! is_array($database)) {
            throw new RuntimeException(
                'A ligação da base de dados de concorrência é inválida.',
            );
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
        $environment['CACHE_STORE'] = 'array';
        $environment['SESSION_DRIVER'] = 'array';

        return $environment;
    }
}
