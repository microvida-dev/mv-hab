<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lottery_runs', function (Blueprint $table) {
            if (! Schema::hasColumn('lottery_runs', 'draw_type')) {
                $table->string('draw_type', 80)->default('general')->after('status')->index();
            }

            if (! Schema::hasColumn('lottery_runs', 'participants_hash')) {
                $table->string('participants_hash')->nullable()->after('participants_count');
            }

            if (! Schema::hasColumn('lottery_runs', 'seed_hash')) {
                $table->string('seed_hash')->nullable()->after('seed');
            }

            if (! Schema::hasColumn('lottery_runs', 'result_hash')) {
                $table->string('result_hash')->nullable()->after('audit_hash');
            }

            if (! Schema::hasColumn('lottery_runs', 'participants_locked_at')) {
                $table->timestamp('participants_locked_at')->nullable()->after('locked_by');
                $table->unsignedBigInteger('participants_locked_by')->nullable()->after('participants_locked_at');
            }

            if (! Schema::hasColumn('lottery_runs', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('completed_at');
                $table->unsignedBigInteger('validated_by')->nullable()->after('validated_at');
            }

            if (! Schema::hasColumn('lottery_runs', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('failed_at');
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }

            if (! Schema::hasColumn('lottery_runs', 'superseded_by_lottery_run_id')) {
                $table->unsignedBigInteger('superseded_by_lottery_run_id')->nullable()->after('cancellation_reason');
            }

            if (! Schema::hasColumn('lottery_runs', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('algorithm');
                $table->string('location')->nullable()->after('scheduled_at');
                $table->text('instructions')->nullable()->after('location');
                $table->text('public_notice_text')->nullable()->after('instructions');
            }
        });

        Schema::table('lottery_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('lottery_participants', 'status')) {
                $table->string('status', 80)->default('included')->after('participant_number')->index();
            }

            if (! Schema::hasColumn('lottery_participants', 'snapshot')) {
                $table->json('snapshot')->nullable()->after('exclusion_reason');
            }

            if (! Schema::hasColumn('lottery_participants', 'priority_group')) {
                $table->string('priority_group')->nullable()->after('rank_position');
            }

            if (! Schema::hasColumn('lottery_participants', 'previous_score')) {
                $table->decimal('previous_score', 8, 2)->nullable()->after('rank_position');
            }

            if (! Schema::hasColumn('lottery_participants', 'included_at')) {
                $table->timestamp('included_at')->nullable()->after('snapshot');
                $table->timestamp('excluded_at')->nullable()->after('included_at');
                $table->timestamp('notified_at')->nullable()->after('excluded_at');
                $table->timestamp('present_at')->nullable()->after('notified_at');
                $table->timestamp('absent_at')->nullable()->after('present_at');
            }
        });

        Schema::table('lottery_draw_results', function (Blueprint $table) {
            if (! Schema::hasColumn('lottery_draw_results', 'status')) {
                $table->string('status', 80)->default('generated')->after('result_type')->index();
            }

            if (! Schema::hasColumn('lottery_draw_results', 'result_hash')) {
                $table->string('result_hash')->nullable()->after('random_value');
            }

            if (! Schema::hasColumn('lottery_draw_results', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('result_hash');
                $table->unsignedBigInteger('validated_by')->nullable()->after('validated_at');
                $table->timestamp('approved_at')->nullable()->after('validated_by');
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
                $table->timestamp('cancelled_at')->nullable()->after('approved_by');
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
                $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            }
        });

        Schema::create('draw_convocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lottery_run_id');
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lottery_participant_id')->nullable();
            $table->string('status', 80)->default('generated')->index();
            $table->timestamp('scheduled_for');
            $table->string('location');
            $table->text('instructions')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lottery_run_id', 'draw_conv_run_fk')->references('id')->on('lottery_runs')->cascadeOnDelete();
            $table->foreign('contest_id', 'draw_conv_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('application_id', 'draw_conv_application_fk')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('user_id', 'draw_conv_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lottery_participant_id', 'draw_conv_participant_fk')->references('id')->on('lottery_participants')->nullOnDelete();
            $table->foreign('generated_by', 'draw_conv_generated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sent_by', 'draw_conv_sent_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by', 'draw_conv_cancelled_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['lottery_run_id', 'application_id', 'status'], 'draw_conv_run_application_status_idx');
        });

        Schema::create('draw_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lottery_run_id');
            $table->unsignedBigInteger('draw_convocation_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lottery_participant_id')->nullable();
            $table->string('status', 80)->default('pending')->index();
            $table->timestamp('check_in_at')->nullable();
            $table->text('justification')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lottery_run_id', 'draw_att_run_fk')->references('id')->on('lottery_runs')->cascadeOnDelete();
            $table->foreign('draw_convocation_id', 'draw_att_convocation_fk')->references('id')->on('draw_convocations')->nullOnDelete();
            $table->foreign('application_id', 'draw_att_application_fk')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('user_id', 'draw_att_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lottery_participant_id', 'draw_att_participant_fk')->references('id')->on('lottery_participants')->nullOnDelete();
            $table->foreign('registered_by', 'draw_att_registered_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['lottery_run_id', 'application_id'], 'draw_att_run_application_unique');
        });

        Schema::create('winner_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lottery_run_id');
            $table->unsignedBigInteger('lottery_draw_result_id');
            $table->unsignedBigInteger('allocation_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contest_housing_unit_id')->nullable();
            $table->unsignedBigInteger('housing_unit_id')->nullable();
            $table->string('status', 80)->default('active')->index();
            $table->timestamp('registered_at')->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->text('validation_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lottery_run_id', 'winner_run_fk')->references('id')->on('lottery_runs')->cascadeOnDelete();
            $table->foreign('lottery_draw_result_id', 'winner_result_fk')->references('id')->on('lottery_draw_results')->cascadeOnDelete();
            $table->foreign('allocation_id', 'winner_allocation_fk')->references('id')->on('allocations')->nullOnDelete();
            $table->foreign('application_id', 'winner_application_fk')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('user_id', 'winner_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('contest_housing_unit_id', 'winner_chu_fk')->references('id')->on('contest_housing_units')->nullOnDelete();
            $table->foreign('housing_unit_id', 'winner_housing_unit_fk')->references('id')->on('housing_units')->nullOnDelete();
            $table->foreign('registered_by', 'winner_registered_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('lottery_draw_result_id', 'winner_result_unique');
            $table->index(['housing_unit_id', 'status'], 'winner_housing_status_idx');
        });

        Schema::create('ranking_update_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lottery_run_id');
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('status', 80)->default('pending')->index();
            $table->json('before_snapshot')->nullable();
            $table->json('after_snapshot')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->unsignedBigInteger('reverted_by')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lottery_run_id', 'ranking_update_run_fk')->references('id')->on('lottery_runs')->cascadeOnDelete();
            $table->foreign('contest_id', 'ranking_update_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('applied_by', 'ranking_update_applied_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by', 'ranking_update_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'ranking_update_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reverted_by', 'ranking_update_reverted_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('post_draw_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lottery_run_id');
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('report_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('html_content')->nullable();
            $table->string('file_disk')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->unsignedBigInteger('downloaded_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lottery_run_id', 'post_draw_report_run_fk')->references('id')->on('lottery_runs')->cascadeOnDelete();
            $table->foreign('contest_id', 'post_draw_report_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('generated_by', 'post_draw_report_generated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('downloaded_by', 'post_draw_report_downloaded_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('key_handover_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('winner_registration_id')->nullable();
            $table->unsignedBigInteger('allocation_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('contest_housing_unit_id')->nullable();
            $table->unsignedBigInteger('housing_unit_id')->nullable();
            $table->string('status', 80)->default('scheduled')->index();
            $table->timestamp('scheduled_for');
            $table->string('location');
            $table->text('instructions')->nullable();
            $table->timestamp('rescheduled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('winner_registration_id', 'key_handover_winner_fk')->references('id')->on('winner_registrations')->nullOnDelete();
            $table->foreign('allocation_id', 'key_handover_allocation_fk')->references('id')->on('allocations')->nullOnDelete();
            $table->foreign('application_id', 'key_handover_application_fk')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('user_id', 'key_handover_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('contest_id', 'key_handover_contest_fk')->references('id')->on('contests')->nullOnDelete();
            $table->foreign('contest_housing_unit_id', 'key_handover_chu_fk')->references('id')->on('contest_housing_units')->nullOnDelete();
            $table->foreign('housing_unit_id', 'key_handover_housing_unit_fk')->references('id')->on('housing_units')->nullOnDelete();
            $table->foreign('cancelled_by', 'key_handover_cancelled_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('completed_by', 'key_handover_completed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status'], 'key_handover_user_status_idx');
        });

        Schema::create('tenant_transitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('winner_registration_id')->nullable();
            $table->unsignedBigInteger('key_handover_appointment_id')->nullable();
            $table->unsignedBigInteger('allocation_id')->nullable();
            $table->unsignedBigInteger('lease_contract_id')->nullable();
            $table->unsignedBigInteger('tenant_financial_account_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('housing_unit_id')->nullable();
            $table->string('status', 80)->default('pending')->index();
            $table->json('preconditions')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('winner_registration_id', 'tenant_transition_winner_fk')->references('id')->on('winner_registrations')->nullOnDelete();
            $table->foreign('key_handover_appointment_id', 'tenant_transition_key_fk')->references('id')->on('key_handover_appointments')->nullOnDelete();
            $table->foreign('allocation_id', 'tenant_transition_allocation_fk')->references('id')->on('allocations')->nullOnDelete();
            $table->foreign('lease_contract_id', 'tenant_transition_contract_fk')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('tenant_financial_account_id', 'tenant_transition_account_fk')->references('id')->on('tenant_financial_accounts')->nullOnDelete();
            $table->foreign('application_id', 'tenant_transition_application_fk')->references('id')->on('applications')->cascadeOnDelete();
            $table->foreign('user_id', 'tenant_transition_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('housing_unit_id', 'tenant_transition_housing_unit_fk')->references('id')->on('housing_units')->nullOnDelete();
            $table->foreign('completed_by', 'tenant_transition_completed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by', 'tenant_transition_cancelled_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'status'], 'tenant_transition_user_status_idx');
        });

        Schema::create('contest_closures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contest_id');
            $table->string('closure_number')->unique();
            $table->string('status', 80)->default('open')->index();
            $table->json('summary')->nullable();
            $table->json('critical_pending_items')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('contest_id', 'contest_closure_contest_fk')->references('id')->on('contests')->cascadeOnDelete();
            $table->foreign('closed_by', 'contest_closure_closed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('archived_by', 'contest_closure_archived_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by', 'contest_closure_cancelled_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('contest_id', 'contest_closure_contest_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contest_closures');
        Schema::dropIfExists('tenant_transitions');
        Schema::dropIfExists('key_handover_appointments');
        Schema::dropIfExists('post_draw_reports');
        Schema::dropIfExists('ranking_update_runs');
        Schema::dropIfExists('winner_registrations');
        Schema::dropIfExists('draw_attendances');
        Schema::dropIfExists('draw_convocations');
    }
};
