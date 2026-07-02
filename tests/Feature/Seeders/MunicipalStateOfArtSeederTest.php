<?php

namespace Tests\Feature\Seeders;

use App\Models\Allocation;
use App\Models\Application;
use App\Models\Contract;
use App\Models\DataSubjectRequest;
use App\Models\DefinitiveList;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use App\Models\HousingVisit;
use App\Models\LeasePayment;
use App\Models\MaintenanceRequest;
use App\Models\Municipality;
use App\Models\OfficialNotification;
use App\Models\PropertyInspection;
use App\Models\ProvisionalList;
use App\Models\RankingEntry;
use App\Models\SupportTicket;
use App\Models\TenantFinancialAccount;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\WorkTask;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MunicipalStateOfArtSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunicipalStateOfArtSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_of_art_seeder_is_idempotent_and_covers_operational_domains(): void
    {
        $this->seed(MunicipalStateOfArtSeeder::class);
        $this->seed(MunicipalStateOfArtSeeder::class);

        $this->assertSame(1, Application::query()->where('application_number', 'E2E-APP-DRAFT-2026')->count());
        $this->assertSame(1, Application::query()->where('application_number', 'E2E-APP-SUBMITTED-2026')->count());
        $this->assertSame(1, Application::query()->where('application_number', 'E2E-APP-CORRECTION-2026')->count());
        $this->assertSame(1, Application::query()->where('application_number', 'E2E-APP-ELIGIBLE-2026')->count());
        $this->assertSame(1, Application::query()->where('application_number', 'E2E-APP-CONTRACT-2026')->count());

        $this->assertTrue(DocumentSubmission::query()->where('title', 'Nota de liquidação validada')->exists());
        $this->assertTrue(DocumentAiAnalysis::query()->where('engine', 'local-demo')->exists());
        $this->assertGreaterThanOrEqual(2, RankingEntry::query()->count());
        $this->assertTrue(ProvisionalList::query()->where('list_number', 'E2E-LP-2026-0001')->exists());
        $this->assertTrue(DefinitiveList::query()->where('list_number', 'E2E-LD-2026-0001')->exists());
        $this->assertTrue(Allocation::query()->whereHas('application', fn ($query) => $query->where('application_number', 'E2E-APP-CONTRACT-2026'))->exists());
        $this->assertTrue(Contract::query()->where('contract_number', 'E2E-CON-2026-0001')->exists());
        $this->assertTrue(TenantProfile::query()->whereHas('user', fn ($query) => $query->where('email', 'e2e.candidato.contract@example.test'))->exists());
        $this->assertTrue(TenantFinancialAccount::query()->where('account_number', 'E2E-FIN-2026-0001')->exists());
        $this->assertTrue(LeasePayment::query()->where('payment_number', 'E2E-PAY-2026-0001')->exists());
        $this->assertTrue(TenantInvoice::query()->where('invoice_number', 'E2E-INV-2026-0001')->exists());
        $this->assertTrue(TenantPayment::query()->where('payment_number', 'E2E-TENPAY-2026-0001')->exists());
        $this->assertTrue(MaintenanceRequest::query()->where('request_number', 'E2E-MAN-2026-0001')->exists());
        $this->assertTrue(PropertyInspection::query()->where('inspection_number', 'E2E-INSP-2026-0001')->exists());
        $this->assertTrue(VisitAvailability::query()->where('title', 'Open house piloto - Monsanto T2')->exists());
        $this->assertTrue(HousingVisit::query()->where('visit_number', 'E2E-VISIT-2026-0001')->exists());
        $this->assertTrue(SupportTicket::query()->where('ticket_number', 'E2E-SUP-2026-0001')->exists());
        $this->assertTrue(OfficialNotification::query()->where('notification_number', 'E2E-NOT-2026-0001')->exists());
        $this->assertTrue(DataSubjectRequest::query()->where('request_number', 'E2E-RGPD-2026-0001')->exists());
        $this->assertTrue(WorkTask::query()->where('task_number', 'E2E-TASK-DOC-2026-0001')->exists());
    }

    public function test_database_seeder_keeps_state_of_art_demo_opt_in(): void
    {
        config(['mvhab.seed_state_of_art_demo' => false]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, DB::table('work_tasks')->whereNotNull('due_at')->count());
        $this->assertSame(0, DB::table('housing_visits')->whereNotNull('scheduled_at')->count());
    }

    public function test_database_seeder_can_load_state_of_art_demo_when_enabled(): void
    {
        config(['mvhab.seed_state_of_art_demo' => true]);

        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, DB::table('work_tasks')->whereNotNull('due_at')->count());
        $this->assertGreaterThan(0, DB::table('housing_visits')->whereNotNull('scheduled_at')->count());
    }

    public function test_state_of_art_seeder_uses_reserved_domains_and_no_real_files(): void
    {
        $this->seed(MunicipalStateOfArtSeeder::class);

        $unexpectedEmails = User::query()
            ->whereNot('email', 'like', '%@example.test')
            ->whereNot('email', 'like', '%@exemplo.pt')
            ->count();

        $this->assertSame(0, $unexpectedEmails);
        $this->assertSame(0, Municipality::query()->whereNotNull('tax_number')->count());
        $this->assertDirectoryDoesNotExist(storage_path('app/private/demo-real-documents'));
    }

    public function test_state_of_art_seeder_applies_known_password_to_demo_users_when_configured(): void
    {
        config(['mvhab.e2e_user_password' => 'pass']);

        $this->seed(MunicipalStateOfArtSeeder::class);

        $passwordHashes = User::query()
            ->where(function ($query): void {
                $query
                    ->where('email', 'like', '%@example.test')
                    ->orWhere('email', 'like', '%@exemplo.pt');
            })
            ->pluck('password', 'email');

        $this->assertNotEmpty($passwordHashes);

        foreach ($passwordHashes as $email => $passwordHash) {
            $this->assertTrue(Hash::check('pass', $passwordHash), sprintf('%s should use the configured demo password.', $email));
        }
    }

    public function test_state_of_art_seeder_creates_future_agenda_events_for_all_tracked_modules(): void
    {
        $this->seed(MunicipalStateOfArtSeeder::class);

        $baseDate = '2026-07-02 09:00:00';

        $trackedFields = [
            'work_tasks' => 'due_at',
            'housing_visits' => 'scheduled_at',
            'property_inspections' => 'scheduled_for',
            'hearings' => 'deadline_at',
            'complaints' => 'submitted_at',
            'maintenance_requests' => 'scheduled_for',
            'maintenance_interventions' => 'scheduled_for',
            'applications' => 'submitted_at',
            'key_handover_appointments' => 'scheduled_for',
            'data_subject_requests' => 'due_at',
            'internal_alerts' => 'due_at',
            'allocation_offers' => 'response_deadline_at',
            'draw_convocations' => 'scheduled_for',
            'rent_installments' => 'overdue_at',
            'additional_document_requests' => 'due_at',
            'additional_information_requests' => 'deadline_at',
            'process_actions' => 'due_at',
            'correction_requests' => 'response_deadline_at',
        ];

        foreach ($trackedFields as $table => $field) {
            $count = DB::table($table)
                ->whereNotNull($field)
                ->where($field, '>', $baseDate)
                ->count();

            $this->assertGreaterThan(0, $count, sprintf('%s.%s should have future demo data.', $table, $field));
        }

        $complaintsWithAdditionalInformationDeadline = DB::table('complaints')
            ->whereNotNull('additional_information_deadline_at')
            ->where('additional_information_deadline_at', '>', $baseDate)
            ->count();

        $this->assertGreaterThan(0, $complaintsWithAdditionalInformationDeadline);
    }
}
