<?php

namespace Tests\Feature\Integrated;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\AllocationStatus;
use App\Enums\ApplicationStatus;
use App\Enums\ArrearStatus;
use App\Enums\ComplaintStatus;
use App\Enums\ContractStatus;
use App\Enums\DefinitiveListStatus;
use App\Enums\EligibilityResult;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\ProvisionalListStatus;
use App\Models\AdministrativeProcess;
use App\Models\Allocation;
use App\Models\Application;
use App\Models\Arrear;
use App\Models\AuditEvent;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\DefinitiveList;
use App\Models\DocumentSubmission;
use App\Models\EligibilityCheck;
use App\Models\MaintenanceRequest;
use App\Models\OfficialNotification;
use App\Models\Program;
use App\Models\ProvisionalList;
use App\Models\ReportExport;
use App\Models\User;
use Database\Seeders\Testing\IntegratedWorkflowTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullHousingProgramFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrated_testing_seeder_creates_traceable_fictitious_flow_scenarios(): void
    {
        $this->seed(IntegratedWorkflowTestSeeder::class);

        $this->assertDatabaseHas('programs', ['slug' => 'programa-qa-integrado-sprint-19']);
        $this->assertDatabaseHas('contests', ['slug' => 'concurso-qa-integrado-sprint-19']);
        $this->assertSame(1, Program::query()->where('slug', 'programa-qa-integrado-sprint-19')->count());
        $this->assertGreaterThanOrEqual(10, User::query()->where('email', 'like', 's19-%@example.test')->count());

        User::query()
            ->where('email', 'like', 's19-%')
            ->pluck('email')
            ->each(fn (string $email) => $this->assertMatchesRegularExpression('/@example\.test$/', $email));

        $this->assertDatabaseHas('applications', ['status' => ApplicationStatus::Submitted->value]);
        $this->assertDatabaseHas('applications', ['status' => ApplicationStatus::Ineligible->value]);
        $this->assertDatabaseHas('applications', ['status' => ApplicationStatus::RequiresCorrection->value]);
        $this->assertGreaterThanOrEqual(8, Application::query()->count());
        $this->assertGreaterThanOrEqual(8, DocumentSubmission::query()->count());

        $this->assertDatabaseHas('eligibility_checks', ['result' => EligibilityResult::Eligible->value]);
        $this->assertDatabaseHas('eligibility_checks', ['result' => EligibilityResult::Ineligible->value]);
        $this->assertGreaterThanOrEqual(8, EligibilityCheck::query()->count());

        $this->assertDatabaseHas('administrative_processes', ['status' => AdministrativeProcessStatus::AdmittedForScoring->value]);
        $this->assertDatabaseHas('administrative_processes', ['status' => AdministrativeProcessStatus::NotAdmitted->value]);
        $this->assertGreaterThanOrEqual(8, AdministrativeProcess::query()->count());

        $this->assertDatabaseHas('provisional_lists', ['status' => ProvisionalListStatus::ComplaintPeriodOpen->value]);
        $this->assertDatabaseHas('complaints', ['status' => ComplaintStatus::Accepted->value]);
        $this->assertDatabaseHas('definitive_lists', ['status' => DefinitiveListStatus::Published->value]);
        $this->assertSame(1, ProvisionalList::query()->count());
        $this->assertSame(1, Complaint::query()->count());
        $this->assertSame(1, DefinitiveList::query()->count());

        $this->assertDatabaseHas('allocations', ['status' => AllocationStatus::ReadyForContract->value]);
        $this->assertDatabaseHas('contracts', ['status' => ContractStatus::Active->value]);
        $this->assertDatabaseHas('arrears', ['status' => ArrearStatus::Open->value]);
        $this->assertDatabaseHas('maintenance_requests', ['status' => MaintenanceRequestStatus::UnderReview->value]);
        $this->assertSame(1, Allocation::query()->count());
        $this->assertSame(1, Contract::query()->processual()->count());
        $this->assertSame(1, Arrear::query()->count());
        $this->assertSame(1, MaintenanceRequest::query()->count());

        $this->assertSame(1, OfficialNotification::query()->where('subject', 'like', '%Sprint 19%')->count());
        $this->assertSame(1, ReportExport::query()->where('file_path', 'reports/testing/s19/quality-export.csv')->count());
        $this->assertDatabaseHas('audit_events', ['event_code' => 'sprint19.integrated_seed.created']);
        $this->assertSame(1, AuditEvent::query()->where('event_code', 'sprint19.integrated_seed.created')->count());
    }

    public function test_core_public_candidate_and_backoffice_pages_render_against_integrated_dataset(): void
    {
        $this->seed(IntegratedWorkflowTestSeeder::class);

        $candidate = User::query()->where('email', 's19-eligible@example.test')->firstOrFail();
        $auditor = User::query()->where('email', 's19-auditor@example.test')->firstOrFail();

        $this->get(route('public.portal'))->assertOk();
        $this->get(route('public.programs.index'))->assertOk()->assertSee('Programa QA Integrado Sprint 19');
        $this->get(route('public.contests.index'))->assertOk()->assertSee('Concurso QA Integrado Sprint 19');

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk()
            ->assertSee('Área pessoal');

        $this->actingAs($auditor)
            ->get(route('backoffice.reports.index'))
            ->assertOk();
    }
}
