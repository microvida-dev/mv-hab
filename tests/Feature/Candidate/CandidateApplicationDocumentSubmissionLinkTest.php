<?php

namespace Tests\Feature\Candidate;

use App\Enums\DocumentAppliesTo;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentType;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Program;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\Documents\DocumentChecklistService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateApplicationDocumentSubmissionLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_review_document_submit_link_uses_stable_target_identifiers(): void
    {
        [$candidate, $application, $member, $requiredDocument] = $this->applicationWithHouseholdMemberDocument();

        $this->actingAs($candidate)
            ->get(route('candidate.applications.review', $application))
            ->assertOk()
            ->assertSee('Submeter')
            ->assertSee('required_document_id='.$requiredDocument->id, false)
            ->assertSee('target_type='.DocumentAppliesTo::HouseholdMember->value, false)
            ->assertSee('target_id='.$member->id, false);
    }

    public function test_candidate_document_create_resolves_application_item_by_stable_target_identifiers(): void
    {
        [$candidate, $application, $member, $requiredDocument, $documentType] = $this->applicationWithHouseholdMemberDocument();
        $checklist = app(DocumentChecklistService::class)->forApplication($application->fresh());

        $this->assertCount(1, $checklist['items']);

        $this->actingAs($candidate)
            ->get(route('candidate.documents.create', [
                'application' => $application->public_id,
                'item' => 'stale-checklist-key',
                'required_document_id' => $requiredDocument->id,
                'target_type' => DocumentAppliesTo::HouseholdMember->value,
                'target_id' => $member->id,
            ]))
            ->assertOk()
            ->assertSee('Submeter documento')
            ->assertSee($documentType->name)
            ->assertSee('name="application_public_id"', false);
    }

    /**
     * @return array{0: User, 1: Application, 2: HouseholdMember, 3: RequiredDocument, 4: DocumentType}
     */
    private function applicationWithHouseholdMemberDocument(): array
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $registration = AdhesionRegistration::factory()
            ->registered()
            ->for($candidate)
            ->create();

        $household = Household::factory()
            ->candidate($registration)
            ->create(['members_count' => 1]);

        $member = HouseholdMember::factory()
            ->applicant()
            ->create([
                'household_id' => $household->id,
                'adhesion_registration_id' => $registration->id,
                'full_name' => 'Candidato Documento Teste',
            ]);

        $currentHousingSituation = CurrentHousingSituation::factory()
            ->for($registration)
            ->create();

        $program = Program::factory()->published()->create();
        $contest = Contest::factory()
            ->open()
            ->for($program)
            ->create();

        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $currentHousingSituation->id,
        ]);

        $documentType = DocumentType::factory()->create([
            'name' => 'Documento obrigatório de teste',
            'applies_to' => DocumentAppliesTo::HouseholdMember->value,
        ]);

        $requiredDocument = RequiredDocument::factory()->create([
            'document_type_id' => $documentType->id,
            'contest_id' => $contest->id,
            'required_for' => DocumentAppliesTo::HouseholdMember->value,
            'sort_order' => 1,
        ]);

        return [$candidate, $application, $member, $requiredDocument, $documentType];
    }
}
