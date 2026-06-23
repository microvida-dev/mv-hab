<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->boolean('is_default')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'score_sets_program_fk')->references('id')->on('programs')->restrictOnDelete();
            $table->foreign('contest_id', 'score_sets_contest_fk')->references('id')->on('contests')->restrictOnDelete();
            $table->foreign('created_by', 'score_sets_creator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'score_sets_updater_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'score_sets_contest_status_idx');
            $table->index(['program_id', 'status'], 'score_sets_program_status_idx');
        });

        Schema::create('scoring_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scoring_rule_set_id');
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100);
            $table->string('target', 100);
            $table->string('calculation_type', 100);
            $table->string('operator', 100)->nullable();
            $table->json('expected_value')->nullable();
            $table->decimal('minimum_value', 15, 2)->nullable();
            $table->decimal('maximum_value', 15, 2)->nullable();
            $table->decimal('points', 12, 2)->nullable();
            $table->decimal('max_points', 12, 2)->nullable();
            $table->decimal('weight', 8, 3)->default(1);
            $table->boolean('requires_manual_review')->default(false);
            $table->boolean('is_exclusionary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('success_message')->nullable();
            $table->text('failure_message')->nullable();
            $table->text('review_message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('scoring_rule_set_id', 'score_criteria_set_fk')
                ->references('id')
                ->on('scoring_rule_sets')
                ->cascadeOnDelete();
            $table->unique(['scoring_rule_set_id', 'code'], 'score_criteria_set_code_unique');
            $table->index(['scoring_rule_set_id', 'is_active'], 'score_criteria_set_active_idx');
        });

        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scoring_criterion_id');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('operator', 100)->nullable();
            $table->json('value')->nullable();
            $table->decimal('minimum_value', 15, 2)->nullable();
            $table->decimal('maximum_value', 15, 2)->nullable();
            $table->decimal('points', 12, 2)->default(0);
            $table->decimal('weight', 8, 3)->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('scoring_criterion_id', 'score_rules_criterion_fk')
                ->references('id')
                ->on('scoring_criteria')
                ->cascadeOnDelete();
            $table->index(['scoring_criterion_id', 'is_active'], 'score_rules_criterion_active_idx');
        });

        Schema::create('tie_breaker_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scoring_rule_set_id');
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('target', 100);
            $table->string('direction', 10)->default('asc');
            $table->unsignedSmallInteger('priority_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('scoring_rule_set_id', 'tie_rules_set_fk')
                ->references('id')
                ->on('scoring_rule_sets')
                ->cascadeOnDelete();
            $table->unique(['scoring_rule_set_id', 'code'], 'tie_rules_set_code_unique');
            $table->index(['scoring_rule_set_id', 'is_active'], 'tie_rules_set_active_idx');
        });

        Schema::create('scoring_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scoring_rule_set_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->unsignedBigInteger('started_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('total_applications')->default(0);
            $table->unsignedInteger('scored_applications')->default(0);
            $table->unsignedInteger('manual_review_applications')->default(0);
            $table->unsignedInteger('excluded_applications')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('scoring_rule_set_id', 'score_runs_set_fk')->references('id')->on('scoring_rule_sets')->restrictOnDelete();
            $table->foreign('program_id', 'score_runs_program_fk')->references('id')->on('programs')->restrictOnDelete();
            $table->foreign('contest_id', 'score_runs_contest_fk')->references('id')->on('contests')->restrictOnDelete();
            $table->foreign('started_by', 'score_runs_starter_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'score_runs_contest_status_idx');
            $table->index(['program_id', 'status'], 'score_runs_program_status_idx');
        });

        Schema::create('application_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scoring_run_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('scoring_rule_set_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 60)->default('pending')->index();
            $table->decimal('total_score', 12, 2)->default(0);
            $table->decimal('automatic_score', 12, 2)->default(0);
            $table->decimal('manual_score', 12, 2)->default(0);
            $table->json('tie_breaker_values')->nullable();
            $table->unsignedInteger('rank_position')->nullable();
            $table->boolean('is_tied')->default(false);
            $table->boolean('requires_manual_review')->default(false);
            $table->boolean('excluded_from_ranking')->default(false);
            $table->text('exclusion_reason')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('scoring_run_id', 'app_scores_run_fk')->references('id')->on('scoring_runs')->cascadeOnDelete();
            $table->foreign('application_id', 'app_scores_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('scoring_rule_set_id', 'app_scores_set_fk')->references('id')->on('scoring_rule_sets')->restrictOnDelete();
            $table->foreign('program_id', 'app_scores_program_fk')->references('id')->on('programs')->restrictOnDelete();
            $table->foreign('contest_id', 'app_scores_contest_fk')->references('id')->on('contests')->restrictOnDelete();
            $table->foreign('user_id', 'app_scores_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('calculated_by', 'app_scores_calculator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('locked_by', 'app_scores_locker_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['scoring_run_id', 'application_id'], 'app_scores_run_application_unique');
            $table->index(['contest_id', 'rank_position'], 'app_scores_contest_rank_idx');
        });

        Schema::create('application_score_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_score_id');
            $table->unsignedBigInteger('scoring_criterion_id');
            $table->unsignedBigInteger('scoring_rule_id')->nullable();
            $table->string('code', 100);
            $table->string('name');
            $table->string('category', 100);
            $table->string('result', 60)->index();
            $table->decimal('points_awarded', 12, 2)->default(0);
            $table->decimal('max_points', 12, 2)->nullable();
            $table->decimal('weight', 8, 3)->default(1);
            $table->json('raw_value')->nullable();
            $table->json('normalized_value')->nullable();
            $table->text('message')->nullable();
            $table->text('technical_message')->nullable();
            $table->boolean('requires_manual_review')->default(false);
            $table->decimal('manual_points', 12, 2)->nullable();
            $table->text('manual_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('application_score_id', 'score_details_score_fk')->references('id')->on('application_scores')->cascadeOnDelete();
            $table->foreign('scoring_criterion_id', 'score_details_criterion_fk')->references('id')->on('scoring_criteria')->restrictOnDelete();
            $table->foreign('scoring_rule_id', 'score_details_rule_fk')->references('id')->on('scoring_rules')->nullOnDelete();
            $table->foreign('reviewed_by', 'score_details_reviewer_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['application_score_id', 'scoring_criterion_id'], 'score_details_score_criterion_unique');
        });

        Schema::create('ranking_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scoring_run_id');
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedInteger('snapshot_number');
            $table->string('status', 40)->default('internal')->index();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('scoring_run_id', 'rank_snapshots_run_fk')->references('id')->on('scoring_runs')->cascadeOnDelete();
            $table->foreign('program_id', 'rank_snapshots_program_fk')->references('id')->on('programs')->restrictOnDelete();
            $table->foreign('contest_id', 'rank_snapshots_contest_fk')->references('id')->on('contests')->restrictOnDelete();
            $table->foreign('generated_by', 'rank_snapshots_generator_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['scoring_run_id', 'snapshot_number'], 'rank_snapshots_run_number_unique');
            $table->index(['contest_id', 'snapshot_number'], 'rank_snapshots_contest_number_idx');
        });

        Schema::create('ranking_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ranking_snapshot_id');
            $table->unsignedBigInteger('application_score_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedInteger('rank_position')->nullable();
            $table->unsignedInteger('previous_rank_position')->nullable();
            $table->decimal('total_score', 12, 2)->default(0);
            $table->json('tie_breaker_values')->nullable();
            $table->boolean('is_tied')->default(false);
            $table->string('status', 60)->index();
            $table->timestamps();

            $table->foreign('ranking_snapshot_id', 'rank_entries_snapshot_fk')->references('id')->on('ranking_snapshots')->cascadeOnDelete();
            $table->foreign('application_score_id', 'rank_entries_score_fk')->references('id')->on('application_scores')->restrictOnDelete();
            $table->foreign('application_id', 'rank_entries_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->unique(['ranking_snapshot_id', 'application_score_id'], 'rank_entries_snapshot_score_unique');
            $table->index(['ranking_snapshot_id', 'rank_position'], 'rank_entries_snapshot_rank_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_entries');
        Schema::dropIfExists('ranking_snapshots');
        Schema::dropIfExists('application_score_details');
        Schema::dropIfExists('application_scores');
        Schema::dropIfExists('scoring_runs');
        Schema::dropIfExists('tie_breaker_rules');
        Schema::dropIfExists('scoring_rules');
        Schema::dropIfExists('scoring_criteria');
        Schema::dropIfExists('scoring_rule_sets');
    }
};
