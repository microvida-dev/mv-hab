<?php

namespace Tests\Feature\UX;

use App\Models\Contest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesEnterpriseCaseFixtures;
use Tests\TestCase;

class ContestCaseWorkspaceTest extends TestCase
{
    use CreatesEnterpriseCaseFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_authorized_user_opens_contest_workspace(): void
    {
        $contest = Contest::factory()->open()->create();

        $this->assertEnterpriseWorkspace('backoffice.cases.contests.show', $contest, 'Concurso');
    }
}
