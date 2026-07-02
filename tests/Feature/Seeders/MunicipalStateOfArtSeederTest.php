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
use Database\Seeders\MunicipalStateOfArtSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
