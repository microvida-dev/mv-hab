<?php

namespace Tests\Feature\Security;

use App\Enums\ContractStatus;
use App\Enums\FinancialTransactionType;
use App\Enums\LeasePaymentStatus;
use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentImportRowStatus;
use App\Enums\PaymentImportStatus;
use App\Enums\PaymentReceiptStatus;
use App\Enums\RentInstallmentStatus;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\FinancialTransaction;
use App\Models\LeasePayment;
use App\Models\Municipality;
use App\Models\PaymentAllocation;
use App\Models\PaymentImportBatch;
use App\Models\PaymentImportRow;
use App\Models\PaymentReceipt;
use App\Models\Program;
use App\Models\RentInstallment;
use App\Models\RentSchedule;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Finance\LeasePaymentService;
use App\Services\Finance\PaymentAllocationService;
use App\Services\Finance\PaymentImportService;
use App\Services\Finance\PaymentReceiptService;
use App\Services\Finance\TenantFinancialAccountService;
use App\Support\DecimalMoney;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinanceTransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        Storage::fake('local');
    }

    public function test_decimal_money_preserves_deterministic_two_decimal_precision(): void
    {
        $this->assertSame(
            '0.30',
            DecimalMoney::normalize(bcadd('0.10', '0.20', 4)),
        );

        $this->assertSame(
            '0.01',
            DecimalMoney::normalize(bcsub('100.00', '99.99', 4)),
        );

        $this->assertSame('100.00', DecimalMoney::normalize('100'));
        $this->assertSame('0.10', DecimalMoney::min('0.10', '0.20'));
        $this->assertSame('0.20', DecimalMoney::max('0.10', '0.20'));
        $this->assertSame('1.00', DecimalMoney::multiply('10.00', '0.10'));
        $this->assertSame('25.00', DecimalMoney::percentage('200.00', '12.5'));

        $this->assertTrue(DecimalMoney::isPositive('0.01'));
        $this->assertFalse(DecimalMoney::isPositive('0.00'));
        $this->assertFalse(DecimalMoney::isPositive('-0.01'));
    }

    public function test_payment_confirmation_and_reversal_are_idempotent_without_duplicate_financial_effects(): void
    {
        $context = $this->financialContext('100.00');
        $this->actingAs($context['manager']);

        $payments = app(LeasePaymentService::class);

        $payment = $payments->store(
            $context['account'],
            $context['manager'],
            [
                'amount' => '100.00',
                'payment_date' => today()->toDateString(),
                'value_date' => today()->toDateString(),
                'method' => 'manual',
                'source' => 'security_test',
                'external_reference' => 'CONFIRM-IDEMPOTENCY-001',
                'payer_name' => $context['tenant']->name,
                'confirm_now' => false,
            ],
        );

        $confirmed = $payments->confirm($payment, $context['manager']);

        $this->assertSame(LeasePaymentStatus::Confirmed, $confirmed->status);
        $this->assertSame('100.00', (string) $confirmed->amount);
        $this->assertSame('100.00', (string) $confirmed->unallocated_amount);

        $this->assertDatabaseCount('lease_payments', 1);
        $this->assertSame(
            1,
            FinancialTransaction::query()
                ->where('transaction_type', FinancialTransactionType::PaymentReceived->value)
                ->where('transactionable_type', $confirmed->getMorphClass())
                ->where('transactionable_id', $confirmed->id)
                ->count(),
        );
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('auditable_type', $confirmed->getMorphClass())
                ->where('auditable_id', $confirmed->id)
                ->where('action', 'lease_payment_confirm')
                ->count(),
        );

        $this->repeatAllowingControlledValidationFailure(
            fn () => $payments->confirm($confirmed->refresh(), $context['manager']),
        );

        $this->assertDatabaseCount('lease_payments', 1);
        $this->assertSame(
            1,
            FinancialTransaction::query()
                ->where('transaction_type', FinancialTransactionType::PaymentReceived->value)
                ->where('transactionable_type', $confirmed->getMorphClass())
                ->where('transactionable_id', $confirmed->id)
                ->count(),
        );
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('auditable_type', $confirmed->getMorphClass())
                ->where('auditable_id', $confirmed->id)
                ->where('action', 'lease_payment_confirm')
                ->count(),
        );

        $reversed = $payments->reverse(
            $confirmed->refresh(),
            $context['manager'],
            'Estorno de integridade transacional.',
        );

        $this->assertSame(LeasePaymentStatus::Reversed, $reversed->status);
        $this->assertSame(
            1,
            FinancialTransaction::query()
                ->where('transaction_type', FinancialTransactionType::PaymentReversed->value)
                ->where('transactionable_type', $reversed->getMorphClass())
                ->where('transactionable_id', $reversed->id)
                ->count(),
        );
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('auditable_type', $reversed->getMorphClass())
                ->where('auditable_id', $reversed->id)
                ->where('action', 'lease_payment_reverse')
                ->count(),
        );

        $balanceAfterFirstReversal = (string) $context['account']->refresh()->current_balance;

        $this->repeatAllowingControlledValidationFailure(
            fn () => $payments->reverse(
                $reversed->refresh(),
                $context['manager'],
                'Segundo estorno que não pode duplicar efeitos.',
            ),
        );

        $this->assertSame(
            1,
            FinancialTransaction::query()
                ->where('transaction_type', FinancialTransactionType::PaymentReversed->value)
                ->where('transactionable_type', $reversed->getMorphClass())
                ->where('transactionable_id', $reversed->id)
                ->count(),
        );
        $this->assertSame(
            $balanceAfterFirstReversal,
            (string) $context['account']->refresh()->current_balance,
        );
    }

    public function test_overpayment_is_capped_to_outstanding_and_receipt_generation_is_idempotent(): void
    {
        $context = $this->financialContext('100.00');
        $this->actingAs($context['manager']);

        $payments = app(LeasePaymentService::class);
        $allocations = app(PaymentAllocationService::class);
        $receipts = app(PaymentReceiptService::class);

        $payment = $payments->store(
            $context['account'],
            $context['manager'],
            [
                'amount' => '150.00',
                'payment_date' => today()->toDateString(),
                'value_date' => today()->toDateString(),
                'method' => 'manual',
                'source' => 'security_test',
                'external_reference' => 'OVERPAYMENT-001',
                'payer_name' => $context['tenant']->name,
                'confirm_now' => true,
            ],
        );

        $allocation = $allocations->allocate(
            $payment->refresh(),
            $context['installment']->refresh(),
            $context['manager'],
        );

        $this->assertSame('100.00', (string) $allocation->amount);
        $this->assertSame(PaymentAllocationStatus::Active, $allocation->status);

        $payment->refresh();
        $context['installment']->refresh();

        $this->assertSame('100.00', (string) $payment->allocated_amount);
        $this->assertSame('50.00', (string) $payment->unallocated_amount);
        $this->assertSame(LeasePaymentStatus::PartiallyAllocated, $payment->status);

        $this->assertSame('100.00', (string) $context['installment']->amount_paid);
        $this->assertSame('0.00', (string) $context['installment']->amount_outstanding);
        $this->assertSame(RentInstallmentStatus::Paid, $context['installment']->status);
        $this->assertSame('0.00', (string) $context['account']->refresh()->current_balance);

        $this->repeatAllowingControlledValidationFailure(
            fn () => $allocations->allocate(
                $payment->refresh(),
                $context['installment']->refresh(),
                $context['manager'],
            ),
        );

        $this->assertSame(
            1,
            PaymentAllocation::query()
                ->where('lease_payment_id', $payment->id)
                ->where('rent_installment_id', $context['installment']->id)
                ->where('status', PaymentAllocationStatus::Active->value)
                ->count(),
        );

        $receipt = $receipts->issue(
            $payment->refresh(),
            $context['manager'],
            'Comprovativo do teste de integridade.',
        );

        $this->assertSame(PaymentReceiptStatus::Issued, $receipt->status);
        $this->assertSame('100.00', (string) $receipt->total_amount);
        $this->assertSame($payment->id, $receipt->lease_payment_id);
        $this->assertNotNull($receipt->storage_path);
        Storage::disk('local')->assertExists((string) $receipt->storage_path);

        $sameReceipt = $receipts->issue(
            $payment->refresh(),
            $context['manager'],
            'Segunda emissão que deve reutilizar o comprovativo.',
        );

        $this->assertSame($receipt->id, $sameReceipt->id);
        $this->assertSame($receipt->receipt_number, $sameReceipt->receipt_number);
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('auditable_type', $receipt->getMorphClass())
                ->where('auditable_id', $receipt->id)
                ->where('action', 'payment_receipt_issue')
                ->count(),
        );
    }

    public function test_payment_import_processing_and_renamed_reimport_do_not_duplicate_payments_or_allocations(): void
    {
        $context = $this->financialContext('80.00');
        $this->actingAs($context['manager']);

        $service = app(PaymentImportService::class);
        $contents = $this->csvFor(
            $context['installment']->reference,
            '80.00',
            today()->toDateString(),
            $context['tenant']->name,
        );

        $batch = $service->store(
            $this->csvUpload($contents, 'pagamentos-julho.csv'),
            $context['manager'],
            'Primeira importação.',
        );

        $this->assertSame($context['municipality']->id, $batch->municipality_id);
        $this->assertSame(PaymentImportStatus::Draft, $batch->status);
        $this->assertSame(1, $batch->rows()->count());
        $this->assertNotNull($batch->storage_path);
        Storage::disk('local')->assertExists((string) $batch->storage_path);

        $processed = $service->process($batch, $context['manager']);

        $this->assertSame(PaymentImportStatus::Processed, $processed->status);
        $this->assertSame(1, $processed->imported_count);
        $this->assertSame(0, $processed->failed_count);

        $row = $processed->rows()->firstOrFail();

        $this->assertSame(PaymentImportRowStatus::Imported, $row->status);
        $this->assertNotNull($row->lease_payment_id);
        $this->assertNotNull($row->rent_installment_id);

        $paymentCount = LeasePayment::query()->count();
        $allocationCount = PaymentAllocation::query()->count();
        $transactionCount = FinancialTransaction::query()->count();

        $service->process($processed->refresh(), $context['manager']);

        $this->assertSame($paymentCount, LeasePayment::query()->count());
        $this->assertSame($allocationCount, PaymentAllocation::query()->count());
        $this->assertSame($transactionCount, FinancialTransaction::query()->count());

        $renamedBatch = $service->store(
            $this->csvUpload($contents, 'pagamentos-julho-renomeado.csv'),
            $context['manager'],
            'Mesmo conteúdo com outro nome.',
        );

        $service->process($renamedBatch, $context['manager']);

        $this->assertSame($paymentCount, LeasePayment::query()->count());
        $this->assertSame($allocationCount, PaymentAllocation::query()->count());

        $renamedRow = $renamedBatch->refresh()->rows()->firstOrFail();

        $this->assertSame(PaymentImportRowStatus::Imported, $renamedRow->status);
        $this->assertSame($row->lease_payment_id, $renamedRow->lease_payment_id);
    }

    public function test_same_import_filename_and_checksum_are_rejected_before_creating_another_batch(): void
    {
        $context = $this->financialContext('75.00');
        $this->actingAs($context['manager']);

        $service = app(PaymentImportService::class);
        $contents = $this->csvFor(
            $context['installment']->reference,
            '75.00',
            today()->toDateString(),
            $context['tenant']->name,
        );

        $service->store(
            $this->csvUpload($contents, 'duplicado.csv'),
            $context['manager'],
        );

        $this->assertDatabaseCount('payment_import_batches', 1);

        try {
            $service->store(
                $this->csvUpload($contents, 'duplicado.csv'),
                $context['manager'],
            );

            $this->fail('Era esperada ValidationException para o mesmo ficheiro e checksum.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }

        $this->assertDatabaseCount('payment_import_batches', 1);
        $this->assertDatabaseCount('payment_import_rows', 1);
    }

    public function test_import_cannot_cross_municipal_boundaries_or_infer_a_null_municipality(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();

        $contextA = $this->financialContext('90.00', $municipalityA);
        $contextB = $this->financialContext('90.00', $municipalityB);

        $this->actingAs($contextA['manager']);

        $service = app(PaymentImportService::class);

        $batchA = $service->store(
            $this->csvUpload(
                $this->csvFor(
                    $contextB['installment']->reference,
                    '90.00',
                    today()->toDateString(),
                    $contextB['tenant']->name,
                ),
                'municipio-a-com-referencia-b.csv',
            ),
            $contextA['manager'],
        );

        $processed = $service->process($batchA, $contextA['manager']);
        $row = $processed->rows()->firstOrFail();

        $this->assertSame(PaymentImportStatus::PartiallyProcessed, $processed->status);
        $this->assertSame(PaymentImportRowStatus::Unmatched, $row->status);
        $this->assertNull($row->lease_payment_id);
        $this->assertSame(0, LeasePayment::query()->count());
        $this->assertSame(0, PaymentAllocation::query()->count());

        $actorWithoutMunicipality = User::factory()->create([
            'municipality_id' => null,
        ]);
        $actorWithoutMunicipality->assignRole('financial_manager');

        try {
            $service->store(
                $this->csvUpload(
                    $this->csvFor(
                        $contextA['installment']->reference,
                        '90.00',
                        today()->toDateString(),
                        $contextA['tenant']->name,
                    ),
                    'sem-municipio.csv',
                ),
                $actorWithoutMunicipality,
            );

            $this->fail('Era esperada ValidationException para importação sem Município.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }

        $this->assertSame(
            1,
            PaymentImportBatch::query()
                ->where('municipality_id', $municipalityA->id)
                ->count(),
        );
        $this->assertSame(
            0,
            PaymentImportBatch::query()
                ->whereNull('municipality_id')
                ->count(),
        );
    }

    /**
     * @return array{
     *     municipality: Municipality,
     *     tenant: User,
     *     manager: User,
     *     contract: Contract,
     *     account: TenantFinancialAccount,
     *     schedule: RentSchedule,
     *     installment: RentInstallment
     * }
     */
    private function financialContext(
        string $amountDue,
        ?Municipality $municipality = null,
    ): array {
        $municipality ??= Municipality::factory()->create();

        $tenant = User::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $tenant->assignRole('candidate');

        $manager = User::factory()->create([
            'municipality_id' => $municipality->id,
            'mfa_required' => true,
        ]);
        $manager->assignRole('financial_manager');

        $program = Program::factory()
            ->published()
            ->create([
                'municipality_id' => $municipality->id,
            ]);

        $contract = Contract::factory()->create([
            'program_id' => $program->id,
            'user_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'tenant_email' => $tenant->email,
            'contract_number' => 'CTR-FIN-'.fake()->unique()->numerify('######'),
            'status' => ContractStatus::Active,
            'monthly_rent' => $amountDue,
            'deposit_amount' => $amountDue,
            'payment_day' => 8,
            'start_date' => today()->startOfMonth()->toDateString(),
            'end_date' => today()->startOfMonth()->addYear()->toDateString(),
            'activated_at' => now(),
            'activated_by' => $manager->id,
        ]);

        $account = app(TenantFinancialAccountService::class)
            ->ensureForContract($contract, $manager);

        $schedule = RentSchedule::factory()->create([
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $contract->id,
            'user_id' => $tenant->id,
            'starts_on' => today()->startOfMonth()->toDateString(),
            'ends_on' => today()->endOfMonth()->toDateString(),
            'monthly_rent' => $amountDue,
            'payment_day' => 8,
            'generated_installments_count' => 1,
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $installment = RentInstallment::factory()->create([
            'tenant_financial_account_id' => $account->id,
            'rent_schedule_id' => $schedule->id,
            'lease_contract_id' => $contract->id,
            'user_id' => $tenant->id,
            'status' => RentInstallmentStatus::Issued,
            'reference' => 'RENT-FIN-'.fake()->unique()->numerify('######'),
            'period_year' => (int) today()->format('Y'),
            'period_month' => (int) today()->format('m'),
            'issue_date' => today()->startOfMonth()->toDateString(),
            'due_date' => today()->day(8)->toDateString(),
            'original_amount' => $amountDue,
            'amount_due' => $amountDue,
            'amount_paid' => '0.00',
            'amount_outstanding' => $amountDue,
            'amount_waived' => '0.00',
            'issued_at' => now(),
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        app(TenantFinancialAccountService::class)->recalculate($account);

        return compact(
            'municipality',
            'tenant',
            'manager',
            'contract',
            'account',
            'schedule',
            'installment',
        );
    }

    private function csvUpload(string $contents, string $filename): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $contents);
    }

    private function csvFor(
        string $reference,
        string $amount,
        string $paymentDate,
        string $payerName,
    ): string {
        return implode("\n", [
            'reference,amount,payment_date,payer_name',
            implode(',', [
                $reference,
                $amount,
                $paymentDate,
                str_replace(',', ' ', $payerName),
            ]),
        ])."\n";
    }

    private function repeatAllowingControlledValidationFailure(callable $operation): void
    {
        try {
            $operation();
        } catch (ValidationException) {
            // A operação repetida pode ser idempotente ou recusada por regra de domínio.
        }
    }
}
