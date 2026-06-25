<?php

namespace Tests\Feature\Rgpd;

use App\Models\DataExportPackage;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\Rgpd\DataExportService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataExportAuthorizationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_rgpd_export_is_private_and_audited(): void
    {
        $actor = $this->userWithRole('administrator');
        $subject = User::factory()->create(['email' => 'titular-export@example.test']);
        $request = DataSubjectRequest::factory()->for($subject)->create();

        $package = app(DataExportService::class)->generate($request, $actor);

        $this->assertInstanceOf(DataExportPackage::class, $package);
        Storage::disk('local')->assertExists($package->storage_path);
        $this->assertStringStartsWith('rgpd/exports/', $package->storage_path);
        $this->assertStringNotContainsString($subject->email, $package->storage_path);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'sensitive_export_created']);
        $this->assertDatabaseHas('sensitive_data_access_logs', [
            'user_id' => $actor->id,
            'resource_id' => $package->id,
            'action' => 'export',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
