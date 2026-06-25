<?php

namespace Tests\Feature\Seeders;

use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\MunicipalPilotStagingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalPilotStagingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipal_pilot_staging_seeder_is_idempotent_for_demo_support_records(): void
    {
        $this->seed(MunicipalPilotStagingSeeder::class);
        $this->seed(MunicipalPilotStagingSeeder::class);

        $ticket = SupportTicket::query()->where('ticket_number', 'SUP-DEMO-2026-000001')->firstOrFail();

        $this->assertSame(1, SupportTicket::query()->where('ticket_number', 'SUP-DEMO-2026-000001')->count());
        $this->assertSame(1, WorkTask::query()->where('source', 'support_ticket:'.$ticket->id)->count());
        $this->assertSame(1, User::query()->where('email', 'atendimento-demo@exemplo.pt')->count());
    }
}
