<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contest_housing_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('housing_unit_id');
            $table->string('status', 80)->default('available')->index();
            $table->timestamp('availability_starts_at')->nullable();
            $table->timestamp('availability_ends_at')->nullable();
            $table->string('typology', 100)->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('max_occupants')->nullable();
            $table->unsignedSmallInteger('min_occupants')->nullable();
            $table->boolean('accessible')->default(false);
            $table->string('reserved_for_special_condition')->nullable();
            $table->decimal('monthly_rent', 10, 2)->nullable();
            $table->decimal('estimated_expenses', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'chu_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'chu_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('housing_unit_id', 'chu_housing_unit_fk')->references('id')->on('housing_units')->restrictOnDelete();
            $table->foreign('created_by', 'chu_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'chu_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'chu_contest_status_idx');
            $table->index(['housing_unit_id', 'status'], 'chu_unit_status_idx');
        });

        Schema::create('typology_adequacy_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('min_household_members')->nullable();
            $table->unsignedSmallInteger('max_household_members')->nullable();
            $table->unsignedSmallInteger('min_adults')->nullable();
            $table->unsignedSmallInteger('max_adults')->nullable();
            $table->unsignedSmallInteger('min_children')->nullable();
            $table->unsignedSmallInteger('max_children')->nullable();
            $table->unsignedTinyInteger('min_bedrooms')->nullable();
            $table->unsignedTinyInteger('max_bedrooms')->nullable();
            $table->string('typology', 100)->nullable();
            $table->boolean('requires_accessibility')->default(false);
            $table->string('special_condition_key')->nullable();
            $table->unsignedInteger('priority_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'typ_rules_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'typ_rules_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->index(['contest_id', 'is_active'], 'typ_rules_contest_active_idx');
            $table->index(['program_id', 'is_active'], 'typ_rules_program_active_idx');
        });

        Schema::create('housing_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contest_id');
            $table->unsignedBigInteger('contest_housing_unit_id');
            $table->unsignedBigInteger('housing_unit_id');
            $table->unsignedInteger('preference_order');
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_id', 'hp_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'hp_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('contest_id', 'hp_contest_fk')->references('id')->on('contests')->restrictOnDelete();
            $table->foreign('contest_housing_unit_id', 'hp_chu_fk')->references('id')->on('contest_housing_units')->restrictOnDelete();
            $table->foreign('housing_unit_id', 'hp_housing_unit_fk')->references('id')->on('housing_units')->restrictOnDelete();
            $table->unique(['application_id', 'preference_order'], 'hp_application_order_unique');
            $table->unique(['application_id', 'contest_housing_unit_id'], 'hp_application_chu_unique');
        });

        Schema::create('allocation_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->string('allocation_method', 80)->default('ranking')->index();
            $table->boolean('allow_preferences')->default(false);
            $table->boolean('allow_lottery')->default(false);
            $table->boolean('allow_manual_override')->default(false);
            $table->boolean('requires_acceptance')->default(true);
            $table->unsignedSmallInteger('acceptance_deadline_days')->default(10);
            $table->boolean('auto_call_next_on_refusal')->default(true);
            $table->boolean('auto_call_next_on_expiry')->default(true);
            $table->unsignedTinyInteger('max_refusals_allowed')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'ars_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'ars_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('created_by', 'ars_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'ars_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'ars_contest_status_idx');
            $table->index(['program_id', 'status'], 'ars_program_status_idx');
        });

        Schema::create('allocation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_rule_set_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('definitive_list_id');
            $table->string('run_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('allocation_method', 80)->index();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->unsignedInteger('total_housing_units')->default(0);
            $table->unsignedInteger('total_candidates')->default(0);
            $table->unsignedInteger('total_allocations')->default(0);
            $table->unsignedInteger('total_reserve_entries')->default(0);
            $table->unsignedInteger('total_refusals')->default(0);
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('allocation_rule_set_id', 'ar_rule_set_fk')->references('id')->on('allocation_rule_sets')->restrictOnDelete();
            $table->foreign('program_id', 'ar_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'ar_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('definitive_list_id', 'ar_def_list_fk')->references('id')->on('definitive_lists')->restrictOnDelete();
            $table->foreign('started_by', 'ar_started_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('locked_by', 'ar_locked_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'ar_contest_status_idx');
        });

        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_run_id');
            $table->unsignedBigInteger('allocation_rule_set_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('definitive_list_id');
            $table->unsignedBigInteger('definitive_list_entry_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contest_housing_unit_id');
            $table->unsignedBigInteger('housing_unit_id');
            $table->string('allocation_method', 80)->index();
            $table->string('status', 80)->default('proposed')->index();
            $table->unsignedInteger('rank_position')->nullable();
            $table->unsignedInteger('reserve_position')->nullable();
            $table->unsignedInteger('preference_order')->nullable();
            $table->unsignedBigInteger('allocated_by')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('acceptance_deadline_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('refused_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ready_for_contract_at')->nullable();
            $table->text('refusal_reason')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('manual_justification')->nullable();
            $table->unsignedBigInteger('superseded_by_allocation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('allocation_run_id', 'alloc_run_fk')->references('id')->on('allocation_runs')->restrictOnDelete();
            $table->foreign('allocation_rule_set_id', 'alloc_rule_set_fk')->references('id')->on('allocation_rule_sets')->restrictOnDelete();
            $table->foreign('program_id', 'alloc_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'alloc_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('definitive_list_id', 'alloc_def_list_fk')->references('id')->on('definitive_lists')->restrictOnDelete();
            $table->foreign('definitive_list_entry_id', 'alloc_def_entry_fk')->references('id')->on('definitive_list_entries')->nullOnDelete();
            $table->foreign('application_id', 'alloc_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'alloc_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('contest_housing_unit_id', 'alloc_chu_fk')->references('id')->on('contest_housing_units')->restrictOnDelete();
            $table->foreign('housing_unit_id', 'alloc_housing_unit_fk')->references('id')->on('housing_units')->restrictOnDelete();
            $table->foreign('allocated_by', 'alloc_allocated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('superseded_by_allocation_id', 'alloc_superseded_by_fk')->references('id')->on('allocations')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'alloc_contest_status_idx');
            $table->index(['application_id', 'status'], 'alloc_application_status_idx');
            $table->index(['contest_housing_unit_id', 'status'], 'alloc_chu_status_idx');
        });

        Schema::create('allocation_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contest_housing_unit_id');
            $table->unsignedBigInteger('housing_unit_id');
            $table->string('offer_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->text('message')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('response_deadline_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('refused_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('candidate_response')->nullable();
            $table->text('candidate_notes')->nullable();
            $table->text('refusal_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('allocation_id', 'offer_allocation_fk')->references('id')->on('allocations')->restrictOnDelete();
            $table->foreign('application_id', 'offer_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'offer_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('contest_housing_unit_id', 'offer_chu_fk')->references('id')->on('contest_housing_units')->restrictOnDelete();
            $table->foreign('housing_unit_id', 'offer_housing_unit_fk')->references('id')->on('housing_units')->restrictOnDelete();
            $table->foreign('issued_by', 'offer_issued_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status'], 'offer_user_status_idx');
        });

        Schema::create('lottery_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_run_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('definitive_list_id');
            $table->string('status', 80)->default('draft')->index();
            $table->string('lottery_method')->default('hash_seeded_order');
            $table->string('seed');
            $table->string('seed_source')->nullable();
            $table->string('algorithm')->default('sha256(seed:participant)');
            $table->unsignedInteger('participants_count')->default(0);
            $table->unsignedInteger('drawn_count')->default(0);
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->string('audit_hash')->nullable();
            $table->json('audit_payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('allocation_run_id', 'lottery_alloc_run_fk')->references('id')->on('allocation_runs')->restrictOnDelete();
            $table->foreign('program_id', 'lottery_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'lottery_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('definitive_list_id', 'lottery_def_list_fk')->references('id')->on('definitive_lists')->restrictOnDelete();
            $table->foreign('started_by', 'lottery_started_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('locked_by', 'lottery_locked_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('lottery_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lottery_run_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('definitive_list_entry_id')->nullable();
            $table->string('participant_number')->index();
            $table->unsignedInteger('rank_position')->nullable();
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_eligible')->default(true);
            $table->text('exclusion_reason')->nullable();
            $table->timestamps();

            $table->foreign('lottery_run_id', 'lp_lottery_run_fk')->references('id')->on('lottery_runs')->cascadeOnDelete();
            $table->foreign('application_id', 'lp_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'lp_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('definitive_list_entry_id', 'lp_def_entry_fk')->references('id')->on('definitive_list_entries')->nullOnDelete();
            $table->unique(['lottery_run_id', 'application_id'], 'lp_run_application_unique');
        });

        Schema::create('lottery_draw_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lottery_run_id');
            $table->unsignedBigInteger('lottery_participant_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('draw_order');
            $table->string('result_type', 80)->index();
            $table->boolean('selected')->default(false);
            $table->unsignedBigInteger('assigned_contest_housing_unit_id')->nullable();
            $table->unsignedBigInteger('assigned_housing_unit_id')->nullable();
            $table->string('random_value');
            $table->json('audit_data')->nullable();
            $table->timestamps();

            $table->foreign('lottery_run_id', 'ldr_lottery_run_fk')->references('id')->on('lottery_runs')->cascadeOnDelete();
            $table->foreign('lottery_participant_id', 'ldr_participant_fk')->references('id')->on('lottery_participants')->cascadeOnDelete();
            $table->foreign('application_id', 'ldr_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'ldr_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_contest_housing_unit_id', 'ldr_assigned_chu_fk')->references('id')->on('contest_housing_units')->nullOnDelete();
            $table->foreign('assigned_housing_unit_id', 'ldr_assigned_unit_fk')->references('id')->on('housing_units')->nullOnDelete();
            $table->unique(['lottery_run_id', 'draw_order'], 'ldr_run_order_unique');
        });

        Schema::create('reserve_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_run_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('definitive_list_id');
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('allocation_run_id', 'reserve_alloc_run_fk')->references('id')->on('allocation_runs')->restrictOnDelete();
            $table->foreign('program_id', 'reserve_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'reserve_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('definitive_list_id', 'reserve_def_list_fk')->references('id')->on('definitive_lists')->restrictOnDelete();
            $table->foreign('generated_by', 'reserve_generated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('locked_by', 'reserve_locked_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('reserve_list_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserve_list_id');
            $table->unsignedBigInteger('allocation_run_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('definitive_list_entry_id')->nullable();
            $table->unsignedInteger('reserve_position');
            $table->string('status', 80)->default('waiting')->index();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('refused_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->unsignedBigInteger('linked_allocation_id')->nullable();
            $table->unsignedBigInteger('replacement_for_allocation_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reserve_list_id', 'rle_reserve_list_fk')->references('id')->on('reserve_lists')->cascadeOnDelete();
            $table->foreign('allocation_run_id', 'rle_alloc_run_fk')->references('id')->on('allocation_runs')->restrictOnDelete();
            $table->foreign('application_id', 'rle_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'rle_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('definitive_list_entry_id', 'rle_def_entry_fk')->references('id')->on('definitive_list_entries')->nullOnDelete();
            $table->foreign('linked_allocation_id', 'rle_linked_alloc_fk')->references('id')->on('allocations')->nullOnDelete();
            $table->foreign('replacement_for_allocation_id', 'rle_replacement_alloc_fk')->references('id')->on('allocations')->nullOnDelete();
            $table->unique(['reserve_list_id', 'reserve_position'], 'rle_list_position_unique');
        });

        Schema::create('allocation_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allocation_run_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('definitive_list_id');
            $table->string('report_number')->unique();
            $table->string('title');
            $table->string('status', 80)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->text('method_description')->nullable();
            $table->text('legal_basis')->nullable();
            $table->json('results_summary')->nullable();
            $table->json('exceptions_summary')->nullable();
            $table->json('refusals_summary')->nullable();
            $table->json('reserve_summary')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_disk')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('allocation_run_id', 'report_alloc_run_fk')->references('id')->on('allocation_runs')->restrictOnDelete();
            $table->foreign('program_id', 'report_program_fk')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('contest_id', 'report_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('definitive_list_id', 'report_def_list_fk')->references('id')->on('definitive_lists')->restrictOnDelete();
            $table->foreign('generated_by', 'report_generated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'report_approved_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_reports');
        Schema::dropIfExists('reserve_list_entries');
        Schema::dropIfExists('reserve_lists');
        Schema::dropIfExists('lottery_draw_results');
        Schema::dropIfExists('lottery_participants');
        Schema::dropIfExists('lottery_runs');
        Schema::dropIfExists('allocation_offers');
        Schema::dropIfExists('allocations');
        Schema::dropIfExists('allocation_runs');
        Schema::dropIfExists('allocation_rule_sets');
        Schema::dropIfExists('housing_preferences');
        Schema::dropIfExists('typology_adequacy_rules');
        Schema::dropIfExists('contest_housing_units');
    }
};
