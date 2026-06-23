<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Enums\DocumentStatus;
use App\Jobs\ProcessDocumentAiJob;
use App\Models\AdhesionRegistration;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\RequiredDocument;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\IncomeSourceSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAiUploadIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_upload_creates_pending_ai_analysis_and_dispatches_queue_job(): void
    {
        Queue::fake();
        Storage::fake('local');
        config(['document-ai.enabled' => true]);
        [$candidate, $registration, $household] = $this->candidateWithDocumentContext();
        $requiredDocument = $this->requiredDocument('documento_identificacao');
        $member = $household->members()->firstOrFail();

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requiredDocument->document_type_id,
                'required_document_id' => $requiredDocument->id,
                'household_member_id' => $member->id,
                'file' => UploadedFile::fake()->create('identificacao-s27.pdf', 100, 'application/pdf'),
                'status' => DocumentStatus::Validated->value,
                'storage_path' => 'public/documento-nao-permitido.pdf',
            ])
            ->assertRedirect();

        $submission = DocumentSubmission::query()->firstOrFail();
        $analysis = DocumentAiAnalysis::query()->firstOrFail();

        $this->assertSame(DocumentStatus::Submitted, $submission->status);
        $this->assertSame($candidate->id, $submission->user_id);
        $this->assertSame($registration->id, $submission->adhesion_registration_id);
        $this->assertSame($submission->id, $analysis->document_submission_id);
        $this->assertSame($submission->current_version_id, $analysis->document_version_id);
        $this->assertSame('pending', $analysis->status->value);
        $this->assertStringStartsWith('documents/'.$registration->id.'/'.$submission->id.'/1/', $submission->storage_path);
        $this->assertStringNotContainsString('public/', $submission->storage_path);
        Storage::disk('local')->assertExists($submission->storage_path);

        Queue::assertPushed(ProcessDocumentAiJob::class, fn (ProcessDocumentAiJob $job): bool => $job->documentAiAnalysisId === $analysis->id);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $analysis->getMorphClass(),
            'auditable_id' => $analysis->id,
            'module' => 'documents',
            'action' => 'document_ai_pending_created',
        ]);
        $this->get('/storage/'.$submission->fresh()->storage_path)->assertForbidden();
    }

    public function test_document_replacement_creates_new_analysis_for_new_version_without_changing_document_workflow(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$candidate, , $household] = $this->candidateWithDocumentContext();
        $requiredDocument = $this->requiredDocument('documento_identificacao');
        $member = $household->members()->firstOrFail();

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requiredDocument->document_type_id,
                'required_document_id' => $requiredDocument->id,
                'household_member_id' => $member->id,
                'file' => UploadedFile::fake()->create('identificacao-v1-s27.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $submission = DocumentSubmission::query()->firstOrFail();
        $submission->forceFill(['status' => DocumentStatus::Rejected])->save();

        $this->actingAs($candidate)
            ->post(route('candidate.documents.replace.store', $submission), [
                'file' => UploadedFile::fake()->create('identificacao-v2-s27.pdf', 90, 'application/pdf'),
            ])
            ->assertRedirect(route('candidate.documents.show', $submission));

        $submission->refresh();
        $this->assertSame(DocumentStatus::Submitted, $submission->status);
        $this->assertSame(2, $submission->versions()->count());
        $this->assertSame(2, DocumentAiAnalysis::query()->where('document_submission_id', $submission->id)->count());
        $this->assertTrue(DocumentAiAnalysis::query()->where('document_version_id', $submission->current_version_id)->exists());
    }

    private function seedDocumentFoundation(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(IncomeSourceSeeder::class);
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);
    }

    /**
     * @return array{0: User, 1: AdhesionRegistration, 2: Household}
     */
    private function candidateWithDocumentContext(): array
    {
        $this->seedDocumentFoundation();
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'email' => $candidate->email,
            'nif' => '270000001',
        ]);
        $household = Household::factory()->candidate($registration)->create();
        $member = HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'is_applicant' => true,
            'full_name' => 'Candidato Ficticio Sprint 27',
            'birth_date' => today()->subYears(35),
            'nif' => '270000002',
        ]);
        $source = IncomeSource::query()->firstOrFail();
        IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'income_source_id' => $source->id,
        ]);
        CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
        ]);

        return [$candidate, $registration, $household];
    }

    private function requiredDocument(string $documentTypeCode): RequiredDocument
    {
        return RequiredDocument::query()
            ->whereHas('documentType', fn ($query) => $query->where('code', $documentTypeCode))
            ->firstOrFail();
    }

    private function userWithRole(string $role): User
    {
        $this->assertTrue(Role::query()->where('name', $role)->exists());
        $user = User::factory()->create(['email' => 's27-'.$role.'-'.fake()->unique()->numerify('####').'@example.test']);
        $user->assignRole($role);

        return $user;
    }
}
