<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->unsignedBigInteger('allocation_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('household_id')->nullable();
            $table->unsignedBigInteger('housing_unit_id')->nullable();
            $table->string('account_number')->unique();
            $table->string('status', 80)->default('active')->index();
            $table->string('currency', 3)->default('EUR');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->decimal('total_issued', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('total_overdue', 12, 2)->default(0);
            $table->decimal('total_waived', 12, 2)->default(0);
            $table->date('next_due_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lease_contract_id', 'fin_accounts_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('application_id', 'fin_accounts_application_fk')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('allocation_id', 'fin_accounts_allocation_fk')->references('id')->on('allocations')->nullOnDelete();
            $table->foreign('user_id', 'fin_accounts_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('household_id', 'fin_accounts_household_fk')->references('id')->on('households')->nullOnDelete();
            $table->foreign('housing_unit_id', 'fin_accounts_housing_fk')->references('id')->on('housing_units')->nullOnDelete();
            $table->foreign('created_by', 'fin_accounts_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'fin_accounts_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('lease_contract_id', 'fin_accounts_contract_unique');
        });

        Schema::create('rent_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('draft')->index();
            $table->string('schedule_type', 80)->default('initial');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->decimal('monthly_rent', 10, 2);
            $table->unsignedTinyInteger('payment_day')->default(8);
            $table->unsignedTinyInteger('issue_day')->default(1);
            $table->unsignedTinyInteger('due_grace_days')->default(0);
            $table->unsignedInteger('generated_installments_count')->default(0);
            $table->unsignedBigInteger('superseded_by_rent_schedule_id')->nullable();
            $table->unsignedBigInteger('source_rent_review_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'rent_schedules_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'rent_schedules_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'rent_schedules_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('superseded_by_rent_schedule_id', 'rent_schedules_superseded_fk')->references('id')->on('rent_schedules')->nullOnDelete();
            $table->foreign('created_by', 'rent_schedules_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'rent_schedules_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['lease_contract_id', 'status'], 'rent_schedules_contract_status_idx');
        });

        Schema::create('rent_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('rent_schedule_id')->nullable();
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('scheduled')->index();
            $table->string('reference')->unique();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('issue_date')->nullable();
            $table->date('due_date');
            $table->decimal('original_amount', 10, 2);
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_outstanding', 10, 2)->default(0);
            $table->decimal('amount_waived', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('overdue_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('waiver_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'rent_installments_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('rent_schedule_id', 'rent_installments_schedule_fk')->references('id')->on('rent_schedules')->nullOnDelete();
            $table->foreign('lease_contract_id', 'rent_installments_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'rent_installments_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by', 'rent_installments_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'rent_installments_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['rent_schedule_id', 'period_year', 'period_month'], 'rent_installments_period_unique');
            $table->index(['lease_contract_id', 'status', 'due_date'], 'rent_installments_status_due_idx');
        });

        Schema::create('lease_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('pending')->index();
            $table->string('payment_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('allocated_amount', 10, 2)->default(0);
            $table->decimal('unallocated_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->date('payment_date');
            $table->date('value_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->string('method', 80)->default('manual');
            $table->string('source', 80)->default('backoffice');
            $table->string('external_reference')->nullable();
            $table->string('payer_name')->nullable();
            $table->text('notes')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'lease_payments_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'lease_payments_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'lease_payments_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by', 'lease_payments_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('confirmed_by', 'lease_payments_confirmed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversed_by', 'lease_payments_reversed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['lease_contract_id', 'status'], 'lease_payments_contract_status_idx');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_payment_id');
            $table->unsignedBigInteger('rent_installment_id');
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('active')->index();
            $table->decimal('amount', 10, 2);
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamps();

            $table->foreign('lease_payment_id', 'payment_allocations_payment_fk')->references('id')->on('lease_payments')->cascadeOnDelete();
            $table->foreign('rent_installment_id', 'payment_allocations_inst_fk')->references('id')->on('rent_installments')->cascadeOnDelete();
            $table->foreign('tenant_financial_account_id', 'payment_allocations_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'payment_allocations_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'payment_allocations_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by', 'payment_allocations_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversed_by', 'payment_allocations_reversed_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('payment_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('original_filename')->nullable();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'payment_imports_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('processed_by', 'payment_imports_processed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversed_by', 'payment_imports_reversed_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('payment_import_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_import_batch_id');
            $table->unsignedBigInteger('lease_payment_id')->nullable();
            $table->unsignedBigInteger('rent_installment_id')->nullable();
            $table->unsignedBigInteger('tenant_financial_account_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 80)->default('pending')->index();
            $table->unsignedInteger('row_number');
            $table->string('reference')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payer_name')->nullable();
            $table->json('raw_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->foreign('payment_import_batch_id', 'payment_rows_batch_fk')->references('id')->on('payment_import_batches')->cascadeOnDelete();
            $table->foreign('lease_payment_id', 'payment_rows_payment_fk')->references('id')->on('lease_payments')->nullOnDelete();
            $table->foreign('rent_installment_id', 'payment_rows_installment_fk')->references('id')->on('rent_installments')->nullOnDelete();
            $table->foreign('tenant_financial_account_id', 'payment_rows_account_fk')->references('id')->on('tenant_financial_accounts')->nullOnDelete();
            $table->foreign('user_id', 'payment_rows_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['payment_import_batch_id', 'row_number'], 'payment_rows_batch_number_unique');
        });

        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_payment_id');
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('receipt_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('reissued_at')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('checksum')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lease_payment_id', 'payment_receipts_payment_fk')->references('id')->on('lease_payments')->cascadeOnDelete();
            $table->foreign('tenant_financial_account_id', 'payment_receipts_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'payment_receipts_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'payment_receipts_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('issued_by', 'payment_receipts_issued_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by', 'payment_receipts_cancelled_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('transaction_type', 80)->index();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('description')->nullable();
            $table->nullableMorphs('transactionable', 'financial_transactionable_idx');
            $table->timestamp('occurred_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_financial_account_id', 'financial_tx_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'financial_tx_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'financial_tx_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by', 'financial_tx_created_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('arrears', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('rent_installment_id');
            $table->unsignedBigInteger('regularization_agreement_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('open')->index();
            $table->decimal('original_amount', 10, 2);
            $table->decimal('outstanding_amount', 10, 2);
            $table->date('overdue_since');
            $table->unsignedInteger('days_overdue')->default(0);
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('regularized_at')->nullable();
            $table->timestamp('waived_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'arrears_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'arrears_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('rent_installment_id', 'arrears_installment_fk')->references('id')->on('rent_installments')->cascadeOnDelete();
            $table->foreign('user_id', 'arrears_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by', 'arrears_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'arrears_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('rent_installment_id', 'arrears_installment_unique');
        });

        Schema::create('default_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arrear_id')->nullable();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('notice_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('notice_type', 80)->default('payment_default');
            $table->string('subject');
            $table->longText('body');
            $table->decimal('amount_due', 10, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('candidate_visible')->default(false);
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('arrear_id', 'default_notices_arrear_fk')->references('id')->on('arrears')->nullOnDelete();
            $table->foreign('tenant_financial_account_id', 'default_notices_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'default_notices_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'default_notices_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by', 'default_notices_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('issued_by', 'default_notices_issued_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by', 'default_notices_cancelled_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('regularization_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('agreement_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('initial_payment_amount', 10, 2)->default(0);
            $table->unsignedInteger('installment_count')->default(1);
            $table->string('periodicity', 80)->default('monthly');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamp('proposed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('breached_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->longText('terms')->nullable();
            $table->text('legal_basis')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'reg_agreements_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'reg_agreements_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'reg_agreements_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by', 'reg_agreements_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'reg_agreements_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'reg_agreements_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('arrears', function (Blueprint $table) {
            $table->foreign('regularization_agreement_id', 'arrears_reg_agreement_fk')->references('id')->on('regularization_agreements')->nullOnDelete();
        });

        Schema::create('regularization_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regularization_agreement_id');
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('scheduled')->index();
            $table->unsignedInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('overdue_at')->nullable();
            $table->timestamp('waived_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('regularization_agreement_id', 'reg_installments_agreement_fk')->references('id')->on('regularization_agreements')->cascadeOnDelete();
            $table->foreign('tenant_financial_account_id', 'reg_installments_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'reg_installments_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'reg_installments_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['regularization_agreement_id', 'installment_number'], 'reg_installments_number_unique');
        });

        Schema::create('rent_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('household_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->unsignedBigInteger('new_rent_schedule_id')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->string('review_type', 80)->default('annual');
            $table->decimal('current_rent', 10, 2);
            $table->decimal('proposed_rent', 10, 2)->nullable();
            $table->decimal('approved_rent', 10, 2)->nullable();
            $table->date('effective_from')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('income_snapshot')->nullable();
            $table->json('calculation_snapshot')->nullable();
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'rent_reviews_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'rent_reviews_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'rent_reviews_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('household_id', 'rent_reviews_household_fk')->references('id')->on('households')->nullOnDelete();
            $table->foreign('requested_by', 'rent_reviews_requested_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by', 'rent_reviews_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'rent_reviews_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('applied_by', 'rent_reviews_applied_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('new_rent_schedule_id', 'rent_reviews_schedule_fk')->references('id')->on('rent_schedules')->nullOnDelete();
        });

        Schema::create('income_change_declarations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('household_id')->nullable();
            $table->unsignedBigInteger('rent_review_id')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->string('change_type', 80)->default('income_change');
            $table->date('changed_at')->nullable();
            $table->decimal('monthly_income_before', 12, 2)->nullable();
            $table->decimal('monthly_income_after', 12, 2)->nullable();
            $table->text('declared_reason');
            $table->text('candidate_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'income_changes_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'income_changes_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'income_changes_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('household_id', 'income_changes_household_fk')->references('id')->on('households')->nullOnDelete();
            $table->foreign('rent_review_id', 'income_changes_review_fk')->references('id')->on('rent_reviews')->nullOnDelete();
            $table->foreign('reviewed_by', 'income_changes_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('annual_document_update_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('household_id')->nullable();
            $table->string('request_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedSmallInteger('reference_year');
            $table->date('due_date')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('required_document_types')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'annual_updates_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'annual_updates_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'annual_updates_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('household_id', 'annual_updates_household_fk')->references('id')->on('households')->nullOnDelete();
            $table->foreign('requested_by', 'annual_updates_requested_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by', 'annual_updates_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('annual_document_update_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annual_document_update_request_id');
            $table->unsignedBigInteger('document_submission_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('submitted')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('annual_document_update_request_id', 'annual_submissions_request_fk')->references('id')->on('annual_document_update_requests')->cascadeOnDelete();
            $table->foreign('document_submission_id', 'annual_submissions_document_fk')->references('id')->on('document_submissions')->nullOnDelete();
            $table->foreign('user_id', 'annual_submissions_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('reviewed_by', 'annual_submissions_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_document_update_submissions');
        Schema::dropIfExists('annual_document_update_requests');
        Schema::dropIfExists('income_change_declarations');
        Schema::dropIfExists('rent_reviews');
        Schema::dropIfExists('regularization_installments');
        Schema::table('arrears', function (Blueprint $table) {
            $table->dropForeign('arrears_reg_agreement_fk');
        });
        Schema::dropIfExists('regularization_agreements');
        Schema::dropIfExists('default_notices');
        Schema::dropIfExists('arrears');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('payment_receipts');
        Schema::dropIfExists('payment_import_rows');
        Schema::dropIfExists('payment_import_batches');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('lease_payments');
        Schema::dropIfExists('rent_installments');
        Schema::dropIfExists('rent_schedules');
        Schema::dropIfExists('tenant_financial_accounts');
    }
};
