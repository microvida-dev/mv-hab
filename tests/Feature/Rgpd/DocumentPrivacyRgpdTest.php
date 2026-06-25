<?php

namespace Tests\Feature\Rgpd;

use App\Models\AdhesionRegistration;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentPrivacyRgpdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_document_storage_remains_private_for_rgpd_pilot(): void
    {
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create();
        $submission = DocumentSubmission::factory()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'storage_path' => 'documents/qa47/private/documento.pdf',
        ]);
        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'uploaded_by' => $candidate->id,
            'storage_path' => 'documents/qa47/private/documento.pdf',
        ]);
        $submission->forceFill(['current_version_id' => $version->id])->save();

        Storage::disk('local')->put($version->storage_path, 'conteudo ficticio');

        $this->get('/storage/'.$version->storage_path)->assertForbidden();
        $this->actingAs($candidate)
            ->get(route('candidate.documents.download', $submission->refresh()))
            ->assertOk();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
