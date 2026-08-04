<?php

namespace Tests\Feature\Municipalities;

use App\Enums\ContestStatus;
use App\Enums\ProgramStatus;
use App\Models\Contest;
use App\Models\MfaDevice;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InitialMunicipalityCatalogCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_catalog_is_created_in_draft_without_demo_data_or_entitlements(): void
    {
        $operator = $this->globalOperator();
        $municipality = Municipality::factory()->create([
            'name' => 'Município de Alcanena',
            'code' => 'ALCANENA',
            'tax_number' => '506000001',
            'contact_email' => 'habitacao@alcanena.pt',
        ]);

        $exit = Artisan::call('mvhab:municipality:provision-initial-catalog', [
            '--actor-id' => $operator->id,
            '--municipality' => 'ALCANENA',
            '--profile' => 'alcanena-2026',
            '--confirm' => true,
        ]);

        $this->assertSame(0, $exit);
        $program = Program::query()->sole();
        $contest = Contest::query()->sole();

        $this->assertSame($municipality->id, $program->municipality_id);
        $this->assertSame(ProgramStatus::Draft, $program->status);
        $this->assertNull($program->published_at);
        $this->assertNull($program->regulatory_profile_id);
        $this->assertNull($program->regulatory_snapshot_id);
        $this->assertSame(ContestStatus::Draft, $contest->status);
        $this->assertNull($contest->published_at);
        $this->assertNull($contest->regulatory_profile_id);
        $this->assertNull($contest->regulatory_snapshot_id);
        $this->assertDatabaseCount('contest_deadlines', 0);
        $this->assertDatabaseCount('contest_jury_members', 0);
        $this->assertDatabaseCount('contest_housing_units', 0);
        $this->assertDatabaseCount('municipality_feature_entitlements', 0);
    }

    public function test_catalog_preview_is_read_only_and_confirmed_replay_is_idempotent(): void
    {
        $operator = $this->globalOperator();
        Municipality::factory()->create([
            'name' => 'Município de Alcanena',
            'code' => 'ALCANENA',
            'tax_number' => '506000001',
            'contact_email' => 'habitacao@alcanena.pt',
        ]);
        $arguments = [
            '--actor-id' => $operator->id,
            '--municipality' => 'ALCANENA',
            '--profile' => 'alcanena-2026',
        ];

        $this->assertSame(0, Artisan::call(
            'mvhab:municipality:provision-initial-catalog',
            $arguments,
        ));
        $this->assertDatabaseCount('programs', 0);
        $this->assertDatabaseCount('contests', 0);

        $confirmed = [...$arguments, '--confirm' => true];
        $this->assertSame(0, Artisan::call(
            'mvhab:municipality:provision-initial-catalog',
            $confirmed,
        ));
        $this->assertSame(0, Artisan::call(
            'mvhab:municipality:provision-initial-catalog',
            $confirmed,
        ));

        $this->assertDatabaseCount('programs', 1);
        $this->assertDatabaseCount('contests', 1);
        $this->assertStringContainsString('IDEMPOTENT_REPLAY=true', Artisan::output());
    }

    private function globalOperator(): User
    {
        $operator = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
            'mfa_required' => true,
        ]);
        $operator->assignRole('administrator');
        PlatformOperatorAssignment::factory()->create(['user_id' => $operator->id]);
        MfaDevice::factory()->confirmed()->for($operator)->create();

        return $operator;
    }
}
