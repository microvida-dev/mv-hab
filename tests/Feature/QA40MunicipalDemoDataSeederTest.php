<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\ContextualFaq;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Database\Seeders\MunicipalPilotStagingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QA40MunicipalDemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipal_pilot_staging_seeder_creates_safe_alcanena_demo_data(): void
    {
        $this->seed(MunicipalPilotStagingSeeder::class);

        $this->assertDatabaseHas('municipalities', [
            'code' => DemoAlcanenaAffordableRentSeeder::MUNICIPALITY_CODE,
            'name' => 'Município de Alcanena',
        ]);
        $this->assertTrue(Program::query()->where('slug', DemoAlcanenaAffordableRentSeeder::PROGRAM_SLUG)->exists());
        $this->assertTrue(Contest::query()->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)->exists());
        $this->assertGreaterThanOrEqual(4, HousingUnit::query()->where('code', 'like', 'ALC-DEMO-%')->count());
        $this->assertTrue(SupportTicket::query()->where('ticket_number', 'SUP-DEMO-2026-000001')->exists());
        $this->assertTrue(HousingVisit::query()->where('visit_number', 'VIS-DEMO-2026-000001')->exists());
        $this->assertTrue(ContextualFaq::query()->where('question', 'Como posso pedir apoio sobre a minha candidatura?')->exists());
        $this->assertGreaterThanOrEqual(2, WorkTask::query()->count());
    }

    public function test_demo_users_are_fictional_and_do_not_use_trivial_password(): void
    {
        $this->seed(MunicipalPilotStagingSeeder::class);

        $demoUsers = User::query()
            ->where(fn ($query) => $query
                ->where('email', 'like', '%@example.test')
                ->orWhere('email', 'like', '%@exemplo.pt'))
            ->get();

        $this->assertGreaterThanOrEqual(10, $demoUsers->count());

        foreach ($demoUsers as $user) {
            $this->assertMatchesRegularExpression('/@(example\.test|exemplo\.pt)$/', $user->email);
            $this->assertFalse(Hash::check('password', $user->password));
        }

        $this->assertSame(0, Municipality::query()->whereNotNull('tax_number')->count());
    }
}
