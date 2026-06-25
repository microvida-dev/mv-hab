<?php

namespace Tests\Feature\Security;

use App\Models\DataExportPackage;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\Rgpd\DataExportService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SensitiveExportAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_rgpd_export_is_private_minimized_and_audited_on_create_and_download(): void
    {
        Storage::fake('local');
        $actor = $this->userWithRole('administrator');
        $subject = User::factory()->create(['email' => 'qa32-subject@example.test']);
        $request = DataSubjectRequest::factory()->for($subject)->create();

        $package = app(DataExportService::class)->generate($request, $actor);

        $this->assertInstanceOf(DataExportPackage::class, $package);
        Storage::disk('local')->assertExists($package->storage_path);
        $this->assertStringStartsWith('rgpd/exports/', $package->storage_path);
        $this->assertStringNotContainsString($subject->email, $package->storage_path);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'sensitive_export_created',
            'auditable_id' => $package->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'rgpd_export_requested',
            'subject_user_id' => $subject->id,
        ]);

        app(DataExportService::class)->download($package->refresh(), $actor);

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'sensitive_export_downloaded',
            'auditable_id' => $package->id,
        ]);
        $this->assertDatabaseHas('sensitive_data_access_logs', [
            'user_id' => $actor->id,
            'resource_id' => $package->id,
            'action' => 'download',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
