<?php

namespace Tests\Feature\UX;

use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class SupportTicketCaseWorkspaceTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_authorized_user_opens_support_ticket_workspace(): void
    {
        $ticket = SupportTicket::factory()->create();

        $this->assertEnterpriseWorkspace('backoffice.cases.tickets.show', $ticket, 'Pedido de apoio');
    }
}
