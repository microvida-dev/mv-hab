<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\DefaultNoticeStatus;
use App\Enums\IncomeChangeStatus;
use App\Enums\LeasePaymentStatus;
use App\Enums\PaymentReceiptStatus;
use App\Enums\RentReviewStatus;
use App\Models\AnnualDocumentUpdateRequest;
use App\Models\Arrear;
use App\Models\Contract;
use App\Models\DefaultNotice;
use App\Models\IncomeChangeDeclaration;
use App\Models\LeasePayment;
use App\Models\PaymentReceipt;
use App\Models\Program;
use App\Models\RegularizationAgreement;
use App\Models\RentInstallment;
use App\Models\RentReview;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Finance\RentScheduleService;
use App\Services\Finance\TenantFinancialAccountService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint14FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_only_access_own_financial_account(): void
    {
        $context = $this->financeContext();
        $account = app(TenantFinancialAccountService::class)->ensureForContract($context['contract'], $context['manager']);

        $this->actingAs($context['candidate'])
            ->get(route('candidate.finance.index'))
            ->assertOk()
            ->assertSee($account->account_number);

        $this->actingAs($context['candidate'])
            ->get(route('candidate.finance.accounts.show', $account))
            ->assertOk();

        $otherCandidate = User::factory()->create();
        $otherCandidate->assignRole('candidate');

        $this->actingAs($otherCandidate)
            ->get(route('candidate.finance.accounts.show', $account))
            ->assertForbidden();
    }

    public function test_financial_manager_generates_schedule_registers_payment_allocates_and_issues_receipt(): void
    {
        $context = $this->financeContext();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.accounts.store'), [
                'lease_contract_id' => $context['contract']->id,
            ])
            ->assertRedirect();

        $account = TenantFinancialAccount::query()->firstOrFail();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.schedules.generate', $context['contract']), [
                'starts_on' => now()->startOfMonth()->toDateString(),
                'ends_on' => now()->startOfMonth()->addMonths(2)->toDateString(),
                'monthly_rent' => 300,
                'payment_day' => 8,
            ])
            ->assertRedirect();

        $this->assertSame(3, RentInstallment::query()->count());
        $installment = RentInstallment::query()->orderBy('due_date')->firstOrFail();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.payments.store'), [
                'tenant_financial_account_id' => $account->id,
                'amount' => 300,
                'payment_date' => now()->toDateString(),
                'confirm_now' => true,
            ])
            ->assertRedirect();

        $payment = LeasePayment::query()->firstOrFail();
        $this->assertSame(LeasePaymentStatus::Confirmed, $payment->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.payments.allocate', $payment), [
                'rent_installment_id' => $installment->id,
                'amount' => 300,
            ])
            ->assertRedirect();

        $this->assertSame(0.0, (float) $installment->refresh()->amount_outstanding);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.receipts.generate', $payment->refresh()))
            ->assertRedirect();

        $receipt = PaymentReceipt::query()->firstOrFail();
        $this->assertSame(PaymentReceiptStatus::Issued, $receipt->status);

        $this->actingAs($context['candidate'])
            ->get(route('candidate.finance.receipts.show', $receipt))
            ->assertOk()
            ->assertSee($receipt->receipt_number);

        $receipt->forceFill(['storage_path' => null])->save();

        $this->actingAs($context['candidate'])
            ->get(route('candidate.finance.receipts.download', $receipt))
            ->assertNotFound();

        $this->actingAs($context['manager'])
            ->get(route('backoffice.finance.receipts.download', $receipt))
            ->assertNotFound();
    }

    public function test_arrear_detection_notice_and_regularization_agreement_flow(): void
    {
        $context = $this->financeContext();
        $account = app(TenantFinancialAccountService::class)->ensureForContract($context['contract'], $context['manager']);

        app(RentScheduleService::class)->generateForContract($context['contract'], $context['manager'], [
            'starts_on' => now()->subMonths(2)->startOfMonth()->toDateString(),
            'ends_on' => now()->subMonth()->startOfMonth()->toDateString(),
            'monthly_rent' => 300,
        ]);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.accounts.detect-arrears', $account))
            ->assertRedirect();

        $arrear = Arrear::query()->firstOrFail();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.default-notices.store'), [
                'arrear_id' => $arrear->id,
                'subject' => 'Aviso interno de incumprimento',
                'body' => 'Foi detetada renda em atraso.',
            ])
            ->assertRedirect();

        $notice = DefaultNotice::query()->firstOrFail();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.default-notices.issue', $notice))
            ->assertRedirect();

        $this->assertSame(DefaultNoticeStatus::Issued, $notice->refresh()->status);

        $this->actingAs($context['candidate'])
            ->get(route('candidate.finance.default-notices.show', $notice))
            ->assertOk();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.regularization-agreements.store'), [
                'tenant_financial_account_id' => $account->id,
                'arrear_ids' => [$arrear->id],
                'installment_count' => 2,
                'starts_on' => now()->addMonth()->startOfMonth()->toDateString(),
            ])
            ->assertRedirect();

        $agreement = RegularizationAgreement::query()->firstOrFail();
        $this->assertSame(2, $agreement->installments()->count());
    }

    public function test_income_change_rent_review_and_annual_document_update_flow(): void
    {
        $context = $this->financeContext();
        $account = app(TenantFinancialAccountService::class)->ensureForContract($context['contract'], $context['manager']);

        $this->actingAs($context['candidate'])
            ->post(route('candidate.finance.income-changes.store'), [
                'tenant_financial_account_id' => $account->id,
                'changed_at' => now()->toDateString(),
                'monthly_income_after' => 950,
                'declared_reason' => 'Alteração sintética de rendimento para teste.',
            ])
            ->assertRedirect();

        $declaration = IncomeChangeDeclaration::query()->firstOrFail();

        $this->actingAs($context['candidate'])
            ->post(route('candidate.finance.income-changes.submit', $declaration))
            ->assertRedirect();

        $this->assertSame(IncomeChangeStatus::Submitted, $declaration->refresh()->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.income-changes.accept', $declaration), [
                'notes' => 'Aceite para revisão.',
            ])
            ->assertRedirect();

        $review = RentReview::query()->firstOrFail();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.rent-reviews.calculate', $review), [
                'proposed_rent' => 275,
            ])
            ->assertRedirect();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.rent-reviews.approve', $review), [
                'approved_rent' => 275,
            ])
            ->assertRedirect();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.rent-reviews.apply', $review->refresh()))
            ->assertRedirect();

        $this->assertSame(RentReviewStatus::Applied, $review->refresh()->status);
        $this->assertSame(275.0, (float) $context['contract']->refresh()->monthly_rent);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.finance.annual-document-updates.store'), [
                'tenant_financial_account_id' => $account->id,
                'reference_year' => now()->year,
            ])
            ->assertRedirect();

        $request = AnnualDocumentUpdateRequest::query()->firstOrFail();

        $this->actingAs($context['candidate'])
            ->post(route('candidate.finance.annual-document-updates.submit', $request), [
                'notes' => 'Sem novos documentos neste teste.',
            ])
            ->assertRedirect();

        $this->assertNotNull($request->refresh()->submitted_at);
    }

    private function financeContext(): array
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $manager = User::factory()->create();
        $manager->assignRole('financial_manager');

        $program = Program::factory()->published()->create();
        $contract = Contract::factory()->create([
            'program_id' => $program->id,
            'user_id' => $candidate->id,
            'tenant_name' => $candidate->name,
            'tenant_email' => $candidate->email,
            'contract_number' => 'CTR-TEST-'.fake()->unique()->numerify('####'),
            'status' => ContractStatus::Active,
            'monthly_rent' => 300,
            'deposit_amount' => 300,
            'payment_day' => 8,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->startOfMonth()->addMonths(5)->toDateString(),
            'activated_at' => now(),
            'activated_by' => $manager->id,
        ]);

        return compact('candidate', 'manager', 'contract');
    }
}
