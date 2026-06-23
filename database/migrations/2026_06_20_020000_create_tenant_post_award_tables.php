<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('pending_activation')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->text('activation_notes')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id', 'tenant_profiles_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by', 'tenant_profiles_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'tenant_profiles_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('user_id', 'tenant_profiles_user_unique');
        });

        Schema::create('tenant_contract_accesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_profile_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('housing_unit_id')->nullable();
            $table->string('status', 80)->default('active')->index();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_profile_id', 'tenant_access_profile_fk')->references('id')->on('tenant_profiles')->cascadeOnDelete();
            $table->foreign('user_id', 'tenant_access_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'tenant_access_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('housing_unit_id', 'tenant_access_housing_fk')->references('id')->on('housing_units')->nullOnDelete();
            $table->foreign('granted_by', 'tenant_access_granted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('revoked_by', 'tenant_access_revoked_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['user_id', 'lease_contract_id'], 'tenant_access_user_contract_unique');
            $table->index(['lease_contract_id', 'status'], 'tenant_access_contract_status_idx');
        });

        Schema::create('tenant_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('housing_unit_id')->nullable();
            $table->unsignedBigInteger('source_rent_installment_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('charge_type', 80)->default('rent')->index();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('original_amount', 12, 2);
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_outstanding', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_financial_account_id', 'tenant_invoices_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'tenant_invoices_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'tenant_invoices_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('housing_unit_id', 'tenant_invoices_housing_fk')->references('id')->on('housing_units')->nullOnDelete();
            $table->foreign('source_rent_installment_id', 'tenant_invoices_installment_fk')->references('id')->on('rent_installments')->nullOnDelete();
            $table->foreign('created_by', 'tenant_invoices_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'tenant_invoices_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_financial_account_id', 'period_year', 'period_month', 'charge_type'], 'tenant_invoices_period_unique');
            $table->index(['lease_contract_id', 'status', 'due_date'], 'tenant_invoices_contract_status_idx');
        });

        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_invoice_id')->nullable();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('source_lease_payment_id')->nullable();
            $table->string('payment_number')->unique();
            $table->string('status', 80)->default('registered')->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->decimal('unallocated_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->date('payment_date');
            $table->date('value_date')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('method', 80)->default('manual');
            $table->string('source', 80)->default('backoffice');
            $table->string('external_reference')->nullable();
            $table->string('payer_name')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('reconciled_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_invoice_id', 'tenant_payments_invoice_fk')->references('id')->on('tenant_invoices')->nullOnDelete();
            $table->foreign('tenant_financial_account_id', 'tenant_payments_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'tenant_payments_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'tenant_payments_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('source_lease_payment_id', 'tenant_payments_lease_payment_fk')->references('id')->on('lease_payments')->nullOnDelete();
            $table->foreign('registered_by', 'tenant_payments_registered_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('confirmed_by', 'tenant_payments_confirmed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reconciled_by', 'tenant_payments_reconciled_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by', 'tenant_payments_cancelled_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['lease_contract_id', 'status'], 'tenant_payments_contract_status_idx');
        });

        Schema::create('tenant_charge_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('charge_type', 80)->default('rent')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('generated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('warnings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'tenant_charge_runs_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['period_year', 'period_month', 'charge_type'], 'tenant_charge_runs_period_unique');
        });

        Schema::create('tenant_charge_run_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_charge_run_id');
            $table->unsignedBigInteger('tenant_invoice_id')->nullable();
            $table->unsignedBigInteger('tenant_financial_account_id');
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('housing_unit_id')->nullable();
            $table->string('status', 80)->default('generated')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_charge_run_id', 'tenant_charge_items_run_fk')->references('id')->on('tenant_charge_runs')->cascadeOnDelete();
            $table->foreign('tenant_invoice_id', 'tenant_charge_items_invoice_fk')->references('id')->on('tenant_invoices')->nullOnDelete();
            $table->foreign('tenant_financial_account_id', 'tenant_charge_items_account_fk')->references('id')->on('tenant_financial_accounts')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'tenant_charge_items_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'tenant_charge_items_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('housing_unit_id', 'tenant_charge_items_housing_fk')->references('id')->on('housing_units')->nullOnDelete();
        });

        Schema::create('tenant_communications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lease_contract_id')->nullable();
            $table->unsignedBigInteger('housing_unit_id')->nullable();
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->string('status', 80)->default('open')->index();
            $table->string('visibility', 80)->default('tenant_and_municipality')->index();
            $table->string('category', 80)->default('general')->index();
            $table->string('priority', 80)->default('normal')->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id', 'tenant_comms_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lease_contract_id', 'tenant_comms_contract_fk')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('housing_unit_id', 'tenant_comms_housing_fk')->references('id')->on('housing_units')->nullOnDelete();
            $table->foreign('created_by', 'tenant_comms_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'tenant_comms_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('tenant_communication_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_communication_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('sender_type', 80)->default('municipality');
            $table->text('body');
            $table->boolean('visible_to_tenant')->default(true);
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_communication_id', 'tenant_comm_msgs_comm_fk')->references('id')->on('tenant_communications')->cascadeOnDelete();
            $table->foreign('user_id', 'tenant_comm_msgs_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'tenant_comm_msgs_created_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('landlord_dashboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->string('status', 80)->default('generated')->index();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedInteger('total_tenants')->default(0);
            $table->unsignedInteger('active_contracts')->default(0);
            $table->unsignedInteger('active_invoices')->default(0);
            $table->unsignedInteger('overdue_invoices')->default(0);
            $table->unsignedInteger('open_maintenance_requests')->default(0);
            $table->unsignedInteger('scheduled_inspections')->default(0);
            $table->unsignedInteger('unread_tenant_messages')->default(0);
            $table->decimal('monthly_billed', 12, 2)->default(0);
            $table->decimal('monthly_collected', 12, 2)->default(0);
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by', 'landlord_snapshots_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('snapshot_date', 'landlord_snapshots_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlord_dashboard_snapshots');
        Schema::dropIfExists('tenant_communication_messages');
        Schema::dropIfExists('tenant_communications');
        Schema::dropIfExists('tenant_charge_run_items');
        Schema::dropIfExists('tenant_charge_runs');
        Schema::dropIfExists('tenant_payments');
        Schema::dropIfExists('tenant_invoices');
        Schema::dropIfExists('tenant_contract_accesses');
        Schema::dropIfExists('tenant_profiles');
    }
};
