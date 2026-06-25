<?php

namespace Tests\Feature;

use App\Enums\AccessLogType;
use App\Enums\DocumentAccessAction;
use App\Models\AccessLog;
use App\Models\AdhesionRegistration;
use App\Models\DocumentAccessLog;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\Role;
use App\Models\SensitiveDataAccessLog;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QA32SecurityRgpdHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_qa32_document_view_download_and_denial_are_audited_without_public_storage_exposure(): void
    {
        Storage::fake('local');
        [$owner, $submission] = $this->documentForCandidate();
        $other = $this->userWithRole('candidate');
        Storage::disk('local')->put($submission->currentVersion->storage_path, 'conteudo documental ficticio');

        $this->actingAs($owner)
            ->get(route('candidate.documents.show', $submission))
            ->assertOk();

        $this->assertDatabaseHas('document_access_logs', [
            'document_submission_id' => $submission->id,
            'action' => DocumentAccessAction::View->value,
        ]);
        $this->assertDatabaseHas('access_logs', [
            'user_id' => $owner->id,
            'access_type' => AccessLogType::DocumentView->value,
            'resource_id' => $submission->id,
        ]);
        $this->assertDatabaseHas('sensitive_data_access_logs', [
            'user_id' => $owner->id,
            'resource_id' => $submission->id,
            'action' => 'view',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'document_viewed',
            'auditable_id' => $submission->id,
        ]);

        $this->actingAs($owner)
            ->get(route('candidate.documents.download', $submission))
            ->assertOk();

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'document_downloaded',
            'auditable_id' => $submission->id,
        ]);

        $this->actingAs($other)
            ->get(route('candidate.documents.download', $submission))
            ->assertForbidden();

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'document_access_denied',
            'auditable_id' => $submission->id,
        ]);
        $this->get('/storage/'.$submission->currentVersion->storage_path)->assertForbidden();
    }

    public function test_qa32_sensitive_access_logs_are_append_only(): void
    {
        $log = AccessLog::factory()->create(['access_type' => AccessLogType::DocumentView]);
        $sensitive = SensitiveDataAccessLog::factory()->create(['action' => 'view']);
        $document = DocumentAccessLog::factory()->create(['action' => DocumentAccessAction::View]);

        $this->assertFalse($log->update(['status_code' => 204]));
        $this->assertFalse($sensitive->update(['action' => 'download']));
        $this->assertFalse($document->update(['action' => DocumentAccessAction::Download]));

        $this->assertSame(AccessLogType::DocumentView, $log->fresh()->access_type);
        $this->assertSame('view', $sensitive->fresh()->action);
        $this->assertSame(DocumentAccessAction::View, $document->fresh()->action);
    }

    /**
     * @return array{0: User, 1: DocumentSubmission}
     */
    private function documentForCandidate(): array
    {
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create();
        $submission = DocumentSubmission::factory()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'storage_path' => 'documents/qa32/private/documento.pdf',
        ]);
        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'uploaded_by' => $candidate->id,
            'storage_path' => 'documents/qa32/private/documento.pdf',
        ]);
        $submission->forceFill(['current_version_id' => $version->id])->save();

        return [$candidate, $submission->refresh()];
    }

    private function userWithRole(string $role): User
    {
        $this->assertTrue(Role::query()->where('name', $role)->exists());

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
