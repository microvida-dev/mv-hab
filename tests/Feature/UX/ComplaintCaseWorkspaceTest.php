<?php

namespace Tests\Feature\UX;

use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class ComplaintCaseWorkspaceTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_authorized_user_opens_complaint_workspace(): void
    {
        $complaint = Complaint::factory()->create();

        $this->assertEnterpriseWorkspace('backoffice.cases.complaints.show', $complaint, 'Reclamação');
    }
}
