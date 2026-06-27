<?php

namespace Tests\Unit\Cases;

use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Cases\NextActionResolver;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextActionResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_pending_documents_resolve_to_document_validation(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('municipal_technician');
        $application = Application::factory()->submitted()->create();
        DocumentSubmission::factory()->create([
            'application_id' => $application->id,
            'status' => 'submitted',
        ]);

        $action = app(NextActionResolver::class)->forApplication($user, $application);

        $this->assertSame('Validar documentos', $action['label']);
        $this->assertTrue($action['enabled']);
    }
}
