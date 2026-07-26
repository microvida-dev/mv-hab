<?php

namespace Tests\Feature\Platform;

use App\Enums\PlatformOperatorGrantSource;
use App\Models\AuditEvent;
use App\Models\MfaDevice;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use RuntimeException;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorBootstrapCommandTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    /** @var list<string> */
    private array $manifestPaths = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->manifestPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_dry_run_is_read_only_and_confirmed_bootstrap_is_idempotent(): void
    {
        $target = $this->platformUser(['platform_operators.view'], assigned: false);
        $rolesBefore = $target->roles()->pluck('roles.id')->all();
        $manifest = $this->manifest([$target->id]);

        $this->bootstrapCommand([
            '--manifest' => $manifest,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain((string) $target->id)
            ->doesntExpectOutputToContain($target->email)
            ->assertSuccessful();

        $this->assertDatabaseCount('platform_operator_assignments', 0);

        $this->bootstrapCommand(['--manifest' => $manifest])
            ->assertFailed();

        $this->bootstrapCommand([
            '--manifest' => $manifest,
            '--confirm' => true,
        ])->assertSuccessful();
        $this->bootstrapCommand([
            '--manifest' => $manifest,
            '--confirm' => true,
        ])->assertSuccessful();

        $assignment = PlatformOperatorAssignment::query()->sole();
        $this->assertSame(PlatformOperatorGrantSource::Bootstrap, $assignment->grant_source);
        $this->assertSame($rolesBefore, $target->roles()->pluck('roles.id')->all());
        $this->assertDatabaseCount('platform_operator_assignments', 1);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'platform_operator_bootstrapped',
            'subject_user_id' => $target->id,
            'user_id' => null,
        ]);
        $this->assertSame(1, AuditEvent::query()
            ->where('event_code', 'platform_operator_bootstrapped')
            ->count());
    }

    public function test_invalid_manifest_environment_and_approval_count_are_rejected(): void
    {
        $target = $this->platformUser(['platform_operators.view'], assigned: false);

        $this->bootstrapCommand([
            '--manifest' => $this->manifest([$target->id], environment: 'production'),
            '--dry-run' => true,
        ])->assertFailed();

        $this->bootstrapCommand([
            '--manifest' => $this->manifest(
                [$target->id],
                approvalReferences: ['SEC-ONLY-001'],
            ),
            '--dry-run' => true,
        ])->assertFailed();

        $this->assertDatabaseCount('platform_operator_assignments', 0);
    }

    public function test_ineligible_explicit_targets_are_rejected_without_inferred_replacement(): void
    {
        $candidate = $this->platformUser(['platform_operators.view'], assigned: false);
        $candidate->assignRole('candidate');
        $inactive = $this->platformUser(['platform_operators.view'], assigned: false);
        $inactive->update(['status' => 'inactive']);
        $municipality = Municipality::factory()->create();
        $municipal = $this->platformUser(
            ['platform_operators.view'],
            assigned: false,
            municipalityId: $municipality->id,
        );
        $withoutPermission = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        MfaDevice::factory()->confirmed()->for($withoutPermission)->create();

        foreach ([$candidate, $inactive, $municipal, $withoutPermission] as $target) {
            $this->bootstrapCommand([
                '--manifest' => $this->manifest([$target->id]),
                '--dry-run' => true,
            ])->assertFailed();
        }

        $this->bootstrapCommand([
            '--manifest' => $this->manifest([999999]),
            '--dry-run' => true,
        ])->assertFailed();

        $this->assertDatabaseCount('platform_operator_assignments', 0);
    }

    /**
     * @param  list<int>  $userIds
     * @param  list<string>  $approvalReferences
     */
    private function manifest(
        array $userIds,
        string $environment = 'testing',
        array $approvalReferences = ['SEC-APPROVAL-001', 'MANAGEMENT-APPROVAL-001'],
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'mvhab-platform-operator-');

        if ($path === false) {
            $this->fail('Não foi possível criar o manifesto temporário.');
        }

        file_put_contents($path, json_encode([
            'environment' => $environment,
            'approved_user_ids' => $userIds,
            'approval_references' => $approvalReferences,
            'bootstrap_operator_reference' => 'OPS-RUNBOOK-001',
            'approved_at' => '2026-07-23',
        ], JSON_THROW_ON_ERROR));
        $this->manifestPaths[] = $path;

        return $path;
    }

    /** @param array<string, mixed> $parameters */
    private function bootstrapCommand(array $parameters): PendingCommand
    {
        $command = $this->artisan('platform-operators:bootstrap', $parameters);

        if (! $command instanceof PendingCommand) {
            throw new RuntimeException('O comando não foi inicializado no contexto de teste.');
        }

        return $command;
    }
}
