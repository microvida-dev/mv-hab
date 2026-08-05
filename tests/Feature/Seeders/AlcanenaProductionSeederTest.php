<?php

namespace Tests\Feature\Seeders;

use App\Enums\ContestDeadlineType;
use App\Enums\ContestStatus;
use App\Enums\MunicipalityOnboardingStatus;
use App\Enums\ProgramStatus;
use App\Models\ConsentPurpose;
use App\Models\Contest;
use App\Models\DocumentType;
use App\Models\Municipality;
use App\Models\MunicipalityOnboardingRun;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\Production\AlcanenaProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlcanenaProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_production_baseline_without_publishing_or_creating_a_municipality(): void
    {
        [$municipality] = $this->onboardedMunicipality();
        $municipalityCount = Municipality::query()->count();

        $this->seed(AlcanenaProductionSeeder::class);

        $this->assertSame($municipalityCount, Municipality::query()->count());
        $this->assertSame(16, DocumentType::query()->count());
        $this->assertSame(4, ConsentPurpose::query()->count());

        $program = Program::query()
            ->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)
            ->sole();
        $contest = Contest::query()
            ->where('code', AlcanenaProductionSeeder::CONTEST_CODE)
            ->sole();

        $this->assertSame($municipality->id, $program->municipality_id);
        $this->assertSame(ProgramStatus::Draft, $program->status);
        $this->assertNull($program->published_at);
        $this->assertSame(6, $program->rules()->count());

        $this->assertSame($program->id, $contest->program_id);
        $this->assertSame(ContestStatus::Draft, $contest->status);
        $this->assertNull($contest->published_at);
        $this->assertSame(1, $contest->deadlines()->count());
        $this->assertTrue(
            $contest->deadlines()
                ->where('type', ContestDeadlineType::Applications->value)
                ->exists(),
        );
    }

    public function test_it_is_create_only_and_preserves_manual_changes_on_replay(): void
    {
        $this->onboardedMunicipality();
        $this->seed(AlcanenaProductionSeeder::class);

        $program = Program::query()
            ->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)
            ->sole();
        $program->forceFill(['summary' => 'Resumo validado manualmente.'])->save();

        $this->seed(AlcanenaProductionSeeder::class);

        $freshProgram = $program->fresh();

        $this->assertInstanceOf(Program::class, $freshProgram);
        $this->assertSame('Resumo validado manualmente.', $freshProgram->summary);
        $this->assertSame(16, DocumentType::query()->count());
        $this->assertSame(4, ConsentPurpose::query()->count());
        $this->assertSame(1, Program::query()->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)->count());
        $this->assertSame(1, Contest::query()->where('code', AlcanenaProductionSeeder::CONTEST_CODE)->count());
    }

    public function test_it_fails_closed_without_completed_onboarding(): void
    {
        Municipality::factory()->create([
            'code' => AlcanenaProductionSeeder::MUNICIPALITY_CODE,
            'active' => true,
        ]);

        $this->expectExceptionMessage('Não existe onboarding municipal concluído');

        $this->seed(AlcanenaProductionSeeder::class);
    }

    /** @return array{0: Municipality, 1: User, 2: User} */
    private function onboardedMunicipality(): array
    {
        $municipality = Municipality::factory()->create([
            'code' => AlcanenaProductionSeeder::MUNICIPALITY_CODE,
            'active' => true,
        ]);
        $platformActor = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $municipalAdministrator = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);

        MunicipalityOnboardingRun::query()->create([
            'operation_id' => (string) Str::uuid(),
            'municipality_code' => AlcanenaProductionSeeder::MUNICIPALITY_CODE,
            'municipality_id' => $municipality->id,
            'actor_id' => $platformActor->id,
            'admin_user_id' => $municipalAdministrator->id,
            'status' => MunicipalityOnboardingStatus::Completed,
            'input_fingerprint' => hash('sha256', 'alcanena-production-seeder-test'),
            'role_template_key' => 'municipal-administrator',
            'role_template_version' => 'test-v1',
            'role_template_fingerprint' => hash('sha256', 'municipal-administrator-test-v1'),
            'attempt_count' => 1,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        return [$municipality, $platformActor, $municipalAdministrator];
    }
}
