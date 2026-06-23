<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Enums\HousingStatus;
use App\Enums\IncomeSourceType;
use App\Models\AdhesionRegistration;
use App\Models\CurrentHousingSituation;
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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentSecurityFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_document_upload_download_review_and_cross_candidate_blocking(): void
    {
        Storage::fake('local');
        [$candidate, $registration, $household] = $this->candidateWithDocumentContext();
        [$otherCandidate] = $this->candidateWithDocumentContext('other');
        $technician = $this->userWithRole('municipal_technician');
        $requiredDocument = $this->requiredDocument('documento_identificacao');
        $member = $household->members()->firstOrFail();

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requiredDocument->document_type_id,
                'required_document_id' => $requiredDocument->id,
                'household_member_id' => $member->id,
                'file' => UploadedFile::fake()->create('identificacao-s19.pdf', 100, 'application/pdf'),
                'storage_path' => 'public/leak.pdf',
                'status' => DocumentStatus::Validated->value,
            ])
            ->assertRedirect();

        $submission = DocumentSubmission::query()->firstOrFail();
        $this->assertSame($candidate->id, $submission->user_id);
        $this->assertSame(DocumentStatus::Submitted, $submission->status);
        $this->assertStringStartsWith('documents/'.$registration->id.'/'.$submission->id.'/1/', $submission->storage_path);
        $this->assertStringNotContainsString($registration->nif, $submission->storage_path);
        $this->assertStringNotContainsString('public/', $submission->storage_path);
        Storage::disk('local')->assertExists($submission->storage_path);

        $this->actingAs($otherCandidate)
            ->get(route('candidate.documents.download', $submission))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('candidate.documents.download', $submission))
            ->assertOk();

        $this->actingAs($technician)
            ->get(route('admin.document-reviews.download', $submission))
            ->assertOk();

        $this->actingAs($technician)
            ->post(route('admin.document-reviews.reject', $submission), [
                'rejection_reason' => 'Documento fictício ilegível para regressão Sprint 19.',
                'internal_notes' => 'Nota interna não visível ao candidato.',
            ])
            ->assertRedirect(route('admin.document-reviews.show', $submission));

        $this->actingAs($candidate)
            ->get(route('candidate.documents.show', $submission->fresh()))
            ->assertOk()
            ->assertSee('Documento fictício ilegível')
            ->assertDontSee('Nota interna não visível');

        $this->assertDatabaseHas('document_access_logs', [
            'document_submission_id' => $submission->id,
            'action' => 'download',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'document.downloaded',
        ]);
        $this->get('/storage/'.$submission->fresh()->storage_path)->assertForbidden();
    }

    public function test_upload_rejects_invalid_mime_type_and_size(): void
    {
        Storage::fake('local');
        [$candidate, , $household] = $this->candidateWithDocumentContext();
        $requiredDocument = $this->requiredDocument('documento_identificacao');
        $member = $household->members()->firstOrFail();

        $payload = [
            'document_type_id' => $requiredDocument->document_type_id,
            'required_document_id' => $requiredDocument->id,
            'household_member_id' => $member->id,
        ];

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                ...$payload,
                'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                ...$payload,
                'file' => UploadedFile::fake()->create('documento-grande.pdf', 11000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');
    }

    private function seedDocumentFoundation(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(IncomeSourceSeeder::class);
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);
    }

    private function candidateWithDocumentContext(string $suffix = 'owner'): array
    {
        $this->seedDocumentFoundation();
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'email' => $candidate->email,
            'nif' => 'S19DOC'.fake()->unique()->numerify('####'),
        ]);
        $household = Household::factory()->candidate($registration)->create();
        $member = HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'is_applicant' => true,
            'full_name' => 'Candidato Documental S19 '.$suffix,
            'birth_date' => today()->subYears(35),
            'nif' => 'M19DOC'.fake()->unique()->numerify('####'),
        ]);
        $source = IncomeSource::query()->where('code', IncomeSourceType::Employment->value)->firstOrFail();
        IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'income_source_id' => $source->id,
        ]);
        CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
            'housing_status' => HousingStatus::Rented->value,
            'current_monthly_rent' => 500,
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
        $user = User::factory()->create(['email' => 's19-doc-'.$role.'-'.fake()->unique()->numerify('####').'@example.test']);
        $user->assignRole($role);

        return $user;
    }
}
