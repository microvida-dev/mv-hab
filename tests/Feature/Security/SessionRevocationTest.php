<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Security\SessionRevocationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_session_revocation_deletes_persisted_sessions_and_audits_event(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        DB::table('sessions')->insert([
            [
                'id' => 'qa32-session-1',
                'user_id' => $target->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'QA32',
                'payload' => 'serialized',
                'last_activity' => time(),
            ],
            [
                'id' => 'qa32-session-2',
                'user_id' => $target->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'QA32',
                'payload' => 'serialized',
                'last_activity' => time(),
            ],
        ]);

        $count = app(SessionRevocationService::class)->revokeAllForUser($target, $actor, 'Teste QA32.');

        $this->assertSame(2, $count);
        $this->assertDatabaseMissing('sessions', ['user_id' => $target->id]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'all_sessions_revoked',
            'subject_user_id' => $target->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'session_revoked',
            'subject_user_id' => $target->id,
        ]);
    }
}
