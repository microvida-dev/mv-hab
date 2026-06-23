<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('citizen_id')->nullable()->change();

            $table->string('contract_number')->nullable()->unique()->after('id');
            $table->unsignedBigInteger('program_id')->nullable()->after('housing_unit_id');
            $table->unsignedBigInteger('contest_id')->nullable()->after('program_id');
            $table->unsignedBigInteger('application_id')->nullable()->after('contest_id');
            $table->unsignedBigInteger('allocation_id')->nullable()->unique()->after('application_id');
            $table->unsignedBigInteger('allocation_offer_id')->nullable()->after('allocation_id');
            $table->unsignedBigInteger('user_id')->nullable()->after('allocation_offer_id');
            $table->unsignedBigInteger('household_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('contest_housing_unit_id')->nullable()->after('household_id');
            $table->unsignedBigInteger('rent_calculation_id')->nullable()->after('contest_housing_unit_id');
            $table->unsignedBigInteger('contract_template_id')->nullable()->after('rent_calculation_id');

            $table->string('tenant_name')->nullable()->after('status');
            $table->string('tenant_identification_number')->nullable()->after('tenant_name');
            $table->string('tenant_tax_number', 50)->nullable()->after('tenant_identification_number');
            $table->string('tenant_email')->nullable()->after('tenant_tax_number');
            $table->string('tenant_phone', 50)->nullable()->after('tenant_email');
            $table->string('tenant_address')->nullable()->after('tenant_phone');
            $table->string('landlord_name')->nullable()->after('tenant_address');
            $table->string('landlord_tax_number', 50)->nullable()->after('landlord_name');
            $table->string('landlord_address')->nullable()->after('landlord_tax_number');
            $table->string('landlord_representative')->nullable()->after('landlord_address');
            $table->string('housing_address')->nullable()->after('landlord_representative');
            $table->string('housing_typology', 100)->nullable()->after('housing_address');
            $table->string('housing_floor', 100)->nullable()->after('housing_typology');
            $table->decimal('housing_area', 10, 2)->nullable()->after('housing_floor');
            $table->text('housing_description')->nullable()->after('housing_area');
            $table->unsignedInteger('duration_months')->nullable()->after('end_date');
            $table->boolean('renewal_allowed')->default(false)->after('duration_months');
            $table->text('renewal_terms')->nullable()->after('renewal_allowed');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('monthly_rent');
            $table->unsignedTinyInteger('payment_day')->nullable()->after('deposit_amount');
            $table->text('payment_method_description')->nullable()->after('payment_day');
            $table->text('special_conditions')->nullable()->after('payment_method_description');
            $table->text('internal_notes')->nullable()->after('special_conditions');
            $table->timestamp('issued_at')->nullable()->after('internal_notes');
            $table->unsignedBigInteger('issued_by')->nullable()->after('issued_at');
            $table->timestamp('signed_at')->nullable()->after('issued_by');
            $table->unsignedBigInteger('signed_by')->nullable()->after('signed_at');
            $table->text('signature_notes')->nullable()->after('signed_by');
            $table->timestamp('activated_at')->nullable()->after('signature_notes');
            $table->unsignedBigInteger('activated_by')->nullable()->after('activated_at');
            $table->timestamp('suspended_at')->nullable()->after('activated_by');
            $table->timestamp('terminated_at')->nullable()->after('suspended_at');
            $table->timestamp('renewed_at')->nullable()->after('terminated_at');
            $table->timestamp('cancelled_at')->nullable()->after('renewed_at');
            $table->unsignedBigInteger('created_by')->nullable()->after('cancelled_at');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->softDeletes();

            $table->foreign('program_id', 'contracts_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'contracts_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('application_id', 'contracts_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('allocation_id', 'contracts_allocation_fk')->references('id')->on('allocations')->restrictOnDelete();
            $table->foreign('allocation_offer_id', 'contracts_offer_fk')->references('id')->on('allocation_offers')->nullOnDelete();
            $table->foreign('user_id', 'contracts_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('household_id', 'contracts_household_fk')->references('id')->on('households')->nullOnDelete();
            $table->foreign('contest_housing_unit_id', 'contracts_chu_fk')->references('id')->on('contest_housing_units')->nullOnDelete();
            $table->foreign('issued_by', 'contracts_issued_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('signed_by', 'contracts_signed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('activated_by', 'contracts_activated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'contracts_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'contracts_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'user_id'], 'contracts_status_user_idx');
        });

        Schema::create('rent_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->string('calculation_method', 80)->default('effort_rate');
            $table->string('income_period', 80)->default('monthly');
            $table->string('income_basis', 80)->default('declared_income');
            $table->decimal('effort_rate_percentage', 5, 2)->nullable();
            $table->decimal('minimum_rent', 10, 2)->nullable();
            $table->decimal('maximum_rent', 10, 2)->nullable();
            $table->decimal('minimum_effort_rate_percentage', 5, 2)->nullable();
            $table->decimal('maximum_effort_rate_percentage', 5, 2)->nullable();
            $table->decimal('deposit_months', 5, 2)->nullable();
            $table->decimal('minimum_deposit', 10, 2)->nullable();
            $table->decimal('maximum_deposit', 10, 2)->nullable();
            $table->string('rounding_mode', 80)->default('nearest');
            $table->unsignedTinyInteger('rounding_precision')->default(2);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('requires_manual_approval')->default(true);
            $table->boolean('allow_manual_override')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'rent_rule_sets_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'rent_rule_sets_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('created_by', 'rent_rule_sets_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'rent_rule_sets_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'rent_rule_sets_contest_status_idx');
            $table->index(['program_id', 'status'], 'rent_rule_sets_program_status_idx');
        });

        Schema::create('rent_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rent_rule_set_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rule_type', 80);
            $table->string('operator', 80)->nullable();
            $table->decimal('minimum_value', 12, 2)->nullable();
            $table->decimal('maximum_value', 12, 2)->nullable();
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('minimum_result', 10, 2)->nullable();
            $table->decimal('maximum_result', 10, 2)->nullable();
            $table->unsignedInteger('priority_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('rent_rule_set_id', 'rent_rules_rule_set_fk')->references('id')->on('rent_rule_sets')->cascadeOnDelete();
            $table->index(['rent_rule_set_id', 'is_active'], 'rent_rules_set_active_idx');
        });

        Schema::create('rent_calculations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rent_rule_set_id');
            $table->unsignedBigInteger('allocation_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('household_id')->nullable();
            $table->unsignedBigInteger('housing_unit_id');
            $table->unsignedBigInteger('contest_housing_unit_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->string('calculation_method', 80);
            $table->string('income_basis', 80);
            $table->string('income_period', 80);
            $table->decimal('monthly_household_income', 12, 2)->default(0);
            $table->decimal('annual_household_income', 12, 2)->default(0);
            $table->decimal('monthly_income_per_capita', 12, 2)->default(0);
            $table->decimal('annual_income_per_capita', 12, 2)->default(0);
            $table->decimal('calculated_effort_rate_percentage', 8, 4)->nullable();
            $table->decimal('configured_effort_rate_percentage', 5, 2)->nullable();
            $table->decimal('base_rent', 10, 2)->nullable();
            $table->decimal('minimum_rent', 10, 2)->nullable();
            $table->decimal('maximum_rent', 10, 2)->nullable();
            $table->decimal('applicable_rent', 10, 2)->nullable();
            $table->decimal('manual_rent', 10, 2)->nullable();
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('superseded_by_rent_calculation_id')->nullable();
            $table->text('summary')->nullable();
            $table->text('technical_notes')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('rent_rule_set_id', 'rent_calc_rule_set_fk')->references('id')->on('rent_rule_sets')->restrictOnDelete();
            $table->foreign('allocation_id', 'rent_calc_allocation_fk')->references('id')->on('allocations')->restrictOnDelete();
            $table->foreign('application_id', 'rent_calc_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'rent_calc_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('household_id', 'rent_calc_household_fk')->references('id')->on('households')->nullOnDelete();
            $table->foreign('housing_unit_id', 'rent_calc_housing_unit_fk')->references('id')->on('housing_units')->restrictOnDelete();
            $table->foreign('contest_housing_unit_id', 'rent_calc_chu_fk')->references('id')->on('contest_housing_units')->nullOnDelete();
            $table->foreign('contract_id', 'rent_calc_contract_fk')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('calculated_by', 'rent_calc_calculated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'rent_calc_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('superseded_by_rent_calculation_id', 'rent_calc_superseded_fk')->references('id')->on('rent_calculations')->nullOnDelete();
        });

        Schema::create('rent_calculation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rent_calculation_id');
            $table->unsignedBigInteger('rent_rule_id')->nullable();
            $table->string('code', 120);
            $table->string('name');
            $table->string('rule_type', 80);
            $table->string('result', 80);
            $table->decimal('input_value', 12, 2)->nullable();
            $table->decimal('output_value', 12, 2)->nullable();
            $table->text('message')->nullable();
            $table->text('technical_message')->nullable();
            $table->timestamps();

            $table->foreign('rent_calculation_id', 'rent_calc_details_calc_fk')->references('id')->on('rent_calculations')->cascadeOnDelete();
            $table->foreign('rent_rule_id', 'rent_calc_details_rule_fk')->references('id')->on('rent_rules')->nullOnDelete();
        });

        Schema::create('rent_manual_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rent_calculation_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('status', 80)->default('pending')->index();
            $table->decimal('original_rent', 10, 2);
            $table->decimal('proposed_rent', 10, 2);
            $table->decimal('approved_rent', 10, 2)->nullable();
            $table->text('reason');
            $table->text('legal_basis')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('rent_calculation_id', 'rent_manual_calc_fk')->references('id')->on('rent_calculations')->cascadeOnDelete();
            $table->foreign('requested_by', 'rent_manual_requested_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by', 'rent_manual_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedInteger('version_number')->default(1);
            $table->longText('template_body');
            $table->longText('header_html')->nullable();
            $table->longText('footer_html')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'contract_templates_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'contract_templates_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('created_by', 'contract_templates_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'contract_templates_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('contract_clauses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('code', 120);
            $table->string('title');
            $table->longText('body');
            $table->string('category', 80)->default('general');
            $table->string('status', 80)->default('draft')->index();
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'contract_clauses_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'contract_clauses_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('created_by', 'contract_clauses_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'contract_clauses_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['code', 'program_id', 'contest_id'], 'contract_clauses_context_code_unique');
        });

        Schema::create('contract_template_clauses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_template_id');
            $table->unsignedBigInteger('contract_clause_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('contract_template_id', 'template_clauses_template_fk')->references('id')->on('contract_templates')->cascadeOnDelete();
            $table->foreign('contract_clause_id', 'template_clauses_clause_fk')->references('id')->on('contract_clauses')->cascadeOnDelete();
            $table->unique(['contract_template_id', 'contract_clause_id'], 'template_clause_unique');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreign('rent_calculation_id', 'contracts_rent_calculation_fk')->references('id')->on('rent_calculations')->nullOnDelete();
            $table->foreign('contract_template_id', 'contracts_template_fk')->references('id')->on('contract_templates')->nullOnDelete();
        });

        Schema::create('lease_contract_parties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('party_type', 80);
            $table->string('name');
            $table->string('identification_number')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('representative_name')->nullable();
            $table->string('representative_role')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lease_contract_id', 'lease_parties_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'lease_parties_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('lease_contract_clauses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('contract_clause_id')->nullable();
            $table->string('code', 120);
            $table->string('title');
            $table->longText('body');
            $table->string('category', 80);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('lease_contract_id', 'lease_clauses_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('contract_clause_id', 'lease_clauses_clause_fk')->references('id')->on('contract_clauses')->nullOnDelete();
        });

        Schema::create('contract_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('allocation_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status', 80)->default('pending')->index();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->text('calculation_basis')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('waived_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('retained_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('receipt_reference')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lease_contract_id', 'deposits_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('application_id', 'deposits_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('allocation_id', 'deposits_allocation_fk')->references('id')->on('allocations')->restrictOnDelete();
            $table->foreign('user_id', 'deposits_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by', 'deposits_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'deposits_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('lease_contract_id', 'deposits_contract_unique');
        });

        Schema::create('lease_contract_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_contract_id');
            $table->string('status', 80)->default('draft')->index();
            $table->string('document_type', 80)->default('contract_html');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('title');
            $table->longText('html_content')->nullable();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lease_contract_id', 'lease_docs_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('generated_by', 'lease_docs_generated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('lease_contract_validations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->string('status', 80)->default('pending')->index();
            $table->string('validation_type', 80)->default('final');
            $table->text('summary')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lease_contract_id', 'lease_validations_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('validated_by', 'lease_validations_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('lease_contract_signatures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_contract_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('signature_role', 80);
            $table->string('status', 80)->default('pending')->index();
            $table->string('signed_by_name')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_method', 80)->default('manual');
            $table->string('signature_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lease_contract_id', 'lease_signatures_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('user_id', 'lease_signatures_user_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('lease_contract_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lease_contract_id');
            $table->string('from_status', 80)->nullable();
            $table->string('to_status', 80)->index();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('lease_contract_id', 'lease_status_contract_fk')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('changed_by', 'lease_status_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_contract_status_histories');
        Schema::dropIfExists('lease_contract_signatures');
        Schema::dropIfExists('lease_contract_validations');
        Schema::dropIfExists('lease_contract_documents');
        Schema::dropIfExists('contract_deposits');
        Schema::dropIfExists('lease_contract_clauses');
        Schema::dropIfExists('lease_contract_parties');

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign('contracts_rent_calculation_fk');
            $table->dropForeign('contracts_template_fk');
        });

        Schema::dropIfExists('contract_template_clauses');
        Schema::dropIfExists('contract_clauses');
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('rent_manual_reviews');
        Schema::dropIfExists('rent_calculation_details');
        Schema::dropIfExists('rent_calculations');
        Schema::dropIfExists('rent_rules');
        Schema::dropIfExists('rent_rule_sets');

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign('contracts_program_fk');
            $table->dropForeign('contracts_contest_fk');
            $table->dropForeign('contracts_application_fk');
            $table->dropForeign('contracts_allocation_fk');
            $table->dropForeign('contracts_offer_fk');
            $table->dropForeign('contracts_user_fk');
            $table->dropForeign('contracts_household_fk');
            $table->dropForeign('contracts_chu_fk');
            $table->dropForeign('contracts_issued_by_fk');
            $table->dropForeign('contracts_signed_by_fk');
            $table->dropForeign('contracts_activated_by_fk');
            $table->dropForeign('contracts_created_by_fk');
            $table->dropForeign('contracts_updated_by_fk');
            $table->dropColumn([
                'contract_number',
                'program_id',
                'contest_id',
                'application_id',
                'allocation_id',
                'allocation_offer_id',
                'user_id',
                'household_id',
                'contest_housing_unit_id',
                'rent_calculation_id',
                'contract_template_id',
                'tenant_name',
                'tenant_identification_number',
                'tenant_tax_number',
                'tenant_email',
                'tenant_phone',
                'tenant_address',
                'landlord_name',
                'landlord_tax_number',
                'landlord_address',
                'landlord_representative',
                'housing_address',
                'housing_typology',
                'housing_floor',
                'housing_area',
                'housing_description',
                'duration_months',
                'renewal_allowed',
                'renewal_terms',
                'deposit_amount',
                'payment_day',
                'payment_method_description',
                'special_conditions',
                'internal_notes',
                'issued_at',
                'issued_by',
                'signed_at',
                'signed_by',
                'signature_notes',
                'activated_at',
                'activated_by',
                'suspended_at',
                'terminated_at',
                'renewed_at',
                'cancelled_at',
                'created_by',
                'updated_by',
                'deleted_at',
            ]);
        });
    }
};
