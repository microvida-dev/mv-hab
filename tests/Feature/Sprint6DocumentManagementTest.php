<?php

namespace Tests\Feature;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentStatus;
use App\Enums\HousingStatus;
use App\Enums\IncomeSourceType;
use App\Models\AdhesionRegistration;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\Program;
use App\Models\RequiredDocument;
use App\Models\User;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\IncomeSourceSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint6DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_pages_require_authentication_candidate_role_and_registration(): void
    {
        $this->seedDocumentFoundation();

        $this->get(route('candidate.documents.checklist'))->assertRedirect(route('login'));

        $administrator = $this->userWithRole('administrator');
        $this->actingAs($administrator)
            ->get(route('candidate.documents.checklist'))
            ->assertForbidden();

        $candidate = $this->candidate();
        $this->actingAs($candidate)
            ->get(route('candidate.documents.checklist'))
            ->assertRedirect(route('candidate.registration.create'));
    }

    public function test_candidate_checklist_is_dynamic_and_calculates_missing_documents(): void
    {
        [$candidate] = $this->candidateWithDocumentContext();

        $this->actingAs($candidate)
            ->get(route('candidate.documents.checklist'))
            ->assertOk()
            ->assertSee('Documento de identificação')
            ->assertSee('Atestado médico de incapacidade multiuso')
            ->assertSee('Comprovativo de estudante')
            ->assertSee('Recibos de vencimento')
            ->assertSee('Contrato de arrendamento atual')
            ->assertSee('Declaração sob compromisso de honra')
            ->assertSee('Progresso documental')
            ->assertSee('0%');
    }

    public function test_candidate_can_upload_required_document_to_private_storage(): void
    {
        Storage::fake('local');
        [$candidate, $registration, $household] = $this->candidateWithDocumentContext();
        $member = $household->members()->firstOrFail();
        $requiredDocument = $this->requiredDocument('documento_identificacao');

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requiredDocument->document_type_id,
                'required_document_id' => $requiredDocument->id,
                'household_member_id' => $member->id,
                'file' => UploadedFile::fake()->create('identificacao.pdf', 100, 'application/pdf'),
                'issue_date' => today()->toDateString(),
                'notes' => 'Documento fictício de teste.',
                'user_id' => 999,
                'status' => DocumentStatus::Validated->value,
                'storage_path' => 'public/leak.pdf',
            ])
            ->assertRedirect();

        $submission = DocumentSubmission::query()->firstOrFail();
        $this->assertSame($registration->id, $submission->adhesion_registration_id);
        $this->assertSame($member->id, $submission->household_member_id);
        $this->assertSame(DocumentStatus::Submitted, $submission->status);
        $this->assertSame(1, $submission->versions()->count());
        $this->assertNotNull($submission->checksum);
        $this->assertStringStartsWith('documents/'.$registration->id.'/'.$submission->id.'/1/', $submission->storage_path);
        $this->assertStringNotContainsString($registration->nif, $submission->storage_path);
        $this->assertStringNotContainsString($registration->full_name, $submission->stored_filename);
        Storage::disk('local')->assertExists($submission->storage_path);
        $this->assertDatabaseHas('document_access_logs', [
            'document_submission_id' => $submission->id,
            'action' => 'upload',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $candidate->id,
            'module' => 'documents',
            'action' => 'upload',
        ]);
    }

    public function test_upload_rejects_invalid_type_and_file_over_default_limit(): void
    {
        Storage::fake('local');
        [$candidate, , $household] = $this->candidateWithDocumentContext();
        $member = $household->members()->firstOrFail();
        $requiredDocument = $this->requiredDocument('documento_identificacao');

        $payload = [
            'document_type_id' => $requiredDocument->document_type_id,
            'required_document_id' => $requiredDocument->id,
            'household_member_id' => $member->id,
        ];

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                ...$payload,
                'file' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                ...$payload,
                'file' => UploadedFile::fake()->create('grande.pdf', 11000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_candidate_cannot_access_or_download_another_candidates_document(): void
    {
        Storage::fake('local');
        [$candidate, , $household] = $this->candidateWithDocumentContext();
        [$otherCandidate] = $this->candidateWithDocumentContext();
        $submission = $this->uploadIdentification($candidate, $household->members()->firstOrFail());

        $this->actingAs($otherCandidate)
            ->get(route('candidate.documents.show', $submission))
            ->assertForbidden();

        $this->actingAs($otherCandidate)
            ->get(route('candidate.documents.download', $submission))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('candidate.documents.download', $submission))
            ->assertOk();

        $this->assertDatabaseHas('document_access_logs', [
            'document_submission_id' => $submission->id,
            'action' => 'download',
        ]);
    }

    public function test_candidate_can_replace_rejected_document_and_preserve_versions(): void
    {
        Storage::fake('local');
        [$candidate, , $household] = $this->candidateWithDocumentContext();
        $technician = $this->userWithRole('municipal_technician');
        $submission = $this->uploadIdentification($candidate, $household->members()->firstOrFail());

        $this->actingAs($technician)
            ->post(route('admin.document-reviews.reject', $submission), [
                'rejection_reason' => 'Documento ilegível.',
                'internal_notes' => 'Nota reservada.',
            ])
            ->assertRedirect(route('admin.document-reviews.show', $submission));

        $this->actingAs($candidate)
            ->post(route('candidate.documents.replace.store', $submission), [
                'file' => UploadedFile::fake()->create('identificacao-v2.pdf', 90, 'application/pdf'),
            ])
            ->assertRedirect(route('candidate.documents.show', $submission));

        $submission->refresh();
        $this->assertSame(DocumentStatus::Submitted, $submission->status);
        $this->assertSame(2, $submission->versions()->count());
        $this->assertSame(DocumentStatus::Replaced, $submission->versions()->where('version_number', 1)->firstOrFail()->status_at_upload);
        $this->assertSame(2, $submission->currentVersion->version_number);
        $this->assertDatabaseHas('document_access_logs', [
            'document_submission_id' => $submission->id,
            'action' => 'replace',
        ]);
    }

    public function test_authorized_technician_can_review_validate_and_reject_documents(): void
    {
        Storage::fake('local');
        [$candidate, , $household] = $this->candidateWithDocumentContext();
        $technician = $this->userWithRole('municipal_technician');
        $submission = $this->uploadIdentification($candidate, $household->members()->firstOrFail());

        $this->actingAs($technician)
            ->post(route('admin.document-reviews.under-review', $submission))
            ->assertRedirect(route('admin.document-reviews.show', $submission));

        $this->assertSame(DocumentStatus::UnderReview, $submission->fresh()->status);

        $this->actingAs($technician)
            ->post(route('admin.document-reviews.validate', $submission), [
                'internal_notes' => 'Documento verificado.',
            ])
            ->assertRedirect(route('admin.document-reviews.show', $submission));

        $this->assertSame(DocumentStatus::Validated, $submission->fresh()->status);
        $this->assertDatabaseHas('document_reviews', [
            'document_submission_id' => $submission->id,
            'decision' => 'validated',
        ]);

        $secondSubmission = $this->uploadRequired(
            $candidate,
            $household->members()->firstOrFail(),
            $this->requiredDocument('nif'),
            'nif.pdf',
        );

        $this->actingAs($technician)
            ->post(route('admin.document-reviews.reject', $secondSubmission), [
                'internal_notes' => 'Sem motivo visível.',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($technician)
            ->post(route('admin.document-reviews.reject', $secondSubmission), [
                'rejection_reason' => 'Documento não corresponde ao tipo solicitado.',
                'internal_notes' => 'Nota reservada ao backoffice.',
            ])
            ->assertRedirect(route('admin.document-reviews.show', $secondSubmission));

        $this->actingAs($candidate)
            ->get(route('candidate.documents.show', $secondSubmission))
            ->assertOk()
            ->assertSee('Documento não corresponde ao tipo solicitado.')
            ->assertDontSee('Nota reservada ao backoffice.');
    }

    public function test_admin_can_manage_document_types_and_unique_code_is_enforced(): void
    {
        $this->seedDocumentFoundation();
        $administrator = $this->userWithRole('administrator');

        $payload = [
            'code' => 'comprovativo_teste',
            'name' => 'Comprovativo de teste',
            'category' => 'declaration',
            'applies_to' => 'adhesion_registration',
            'is_active' => true,
            'max_file_size_mb' => 5,
            'allowed_mime_types' => ['application/pdf'],
        ];

        $this->actingAs($administrator)
            ->post(route('admin.document-types.store'), $payload)
            ->assertRedirect(route('admin.document-types.index'));

        $this->assertDatabaseHas('document_types', [
            'code' => 'comprovativo_teste',
            'max_file_size_mb' => 5,
        ]);

        $this->actingAs($administrator)
            ->post(route('admin.document-types.store'), $payload)
            ->assertSessionHasErrors('code');
    }

    public function test_required_documents_can_target_program_contest_and_inactive_types_are_hidden(): void
    {
        $this->seedDocumentFoundation();
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->create();
        $contest = Contest::factory()->for($program)->create();
        $documentType = DocumentType::factory()->create([
            'name' => 'Documento inativo',
            'applies_to' => DocumentAppliesTo::AdhesionRegistration->value,
            'is_active' => false,
        ]);

        $this->actingAs($administrator)
            ->post(route('admin.required-documents.store'), [
                'document_type_id' => $documentType->id,
                'program_id' => $program->id,
                'contest_id' => $contest->id,
                'required_for' => DocumentAppliesTo::AdhesionRegistration->value,
                'condition_key' => 'always',
                'condition_operator' => 'always',
                'is_required' => true,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.required-documents.index'));

        $this->assertDatabaseHas('required_documents', [
            'document_type_id' => $documentType->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);

        [$candidate] = $this->candidateWithDocumentContext();

        $this->actingAs($candidate)
            ->get(route('candidate.documents.checklist'))
            ->assertOk()
            ->assertDontSee('Documento inativo');
    }

    public function test_private_paths_are_not_exposed_as_public_storage_urls(): void
    {
        Storage::fake('local');
        [$candidate, $registration, $household] = $this->candidateWithDocumentContext();
        $submission = $this->uploadIdentification($candidate, $household->members()->firstOrFail());

        $this->actingAs($candidate)
            ->get(route('candidate.documents.show', $submission))
            ->assertOk()
            ->assertDontSee($submission->storage_path)
            ->assertDontSee($submission->checksum);

        $this->get('/storage/'.$submission->storage_path)->assertForbidden();
        $this->assertStringNotContainsString($registration->nif, $submission->storage_path);
        $this->assertStringNotContainsString($registration->full_name, $submission->storage_path);
    }

    private function seedDocumentFoundation(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(IncomeSourceSeeder::class);
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(RequiredDocumentSeeder::class);
    }

    private function candidate(): User
    {
        return $this->userWithRole('candidate');
    }

    private function userWithRole(string $role): User
    {
        $this->seed(SystemAccessSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function candidateWithDocumentContext(): array
    {
        $this->seedDocumentFoundation();
        $candidate = $this->candidate();
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'nif' => 'TEST-DOC-'.fake()->unique()->numerify('####'),
        ]);
        $household = Household::factory()->candidate($registration)->create();
        $member = HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'is_applicant' => true,
            'full_name' => 'Candidato Documental',
            'birth_date' => today()->subYears(35),
            'is_disabled' => true,
            'is_student' => true,
            'nif' => 'MEM-DOC-'.fake()->unique()->numerify('####'),
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

    private function uploadIdentification(User $candidate, HouseholdMember $member): DocumentSubmission
    {
        return $this->uploadRequired(
            $candidate,
            $member,
            $this->requiredDocument('documento_identificacao'),
            'identificacao.pdf',
        );
    }

    private function uploadRequired(
        User $candidate,
        HouseholdMember $member,
        RequiredDocument $requiredDocument,
        string $filename,
    ): DocumentSubmission {
        $this->actingAs($candidate)
            ->post(route('candidate.documents.store'), [
                'document_type_id' => $requiredDocument->document_type_id,
                'required_document_id' => $requiredDocument->id,
                'household_member_id' => $member->id,
                'file' => UploadedFile::fake()->create($filename, 100, 'application/pdf'),
            ])
            ->assertRedirect();

        return DocumentSubmission::query()
            ->where('required_document_id', $requiredDocument->id)
            ->latest()
            ->firstOrFail();
    }
}
