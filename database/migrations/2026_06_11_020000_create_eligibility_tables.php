<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_default')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contest_id', 'status'], 'eligibility_rule_sets_contest_status_idx');
            $table->index(['program_id', 'status'], 'eligibility_rule_sets_program_status_idx');
        });

        Schema::create('eligibility_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eligibility_rule_set_id')->constrained()->cascadeOnDelete();
            $table->string('code', 150);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50);
            $table->string('target', 50);
            $table->string('operator', 80);
            $table->json('expected_value')->nullable();
            $table->decimal('minimum_value', 15, 2)->nullable();
            $table->decimal('maximum_value', 15, 2)->nullable();
            $table->string('unit', 50)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('requires_manual_review')->default(false);
            $table->text('failure_message')->nullable();
            $table->text('success_message')->nullable();
            $table->text('review_message')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['eligibility_rule_set_id', 'code'], 'eligibility_criteria_rule_code_unique');
            $table->index(['eligibility_rule_set_id', 'is_active'], 'eligibility_criteria_rule_active_idx');
        });

        Schema::create('eligibility_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eligibility_rule_set_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('adhesion_registration_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('check_type', 60)->index();
            $table->string('status', 30)->default('draft')->index();
            $table->string('result', 40)->nullable()->index();
            $table->text('summary')->nullable();
            $table->json('missing_data')->nullable();
            $table->json('warnings')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at'], 'eligibility_checks_user_created_idx');
            $table->index(['application_id', 'created_at'], 'eligibility_checks_application_created_idx');
        });

        Schema::create('eligibility_check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eligibility_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eligibility_criterion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 150);
            $table->string('name');
            $table->string('category', 50);
            $table->string('result', 40)->index();
            $table->json('actual_value')->nullable();
            $table->json('expected_value')->nullable();
            $table->string('operator', 80);
            $table->text('message')->nullable();
            $table->text('technical_message')->nullable();
            $table->boolean('requires_manual_review')->default(false);
            $table->timestamps();

            $table->index(['eligibility_check_id', 'result'], 'eligibility_results_check_result_idx');
        });

        Schema::create('eligibility_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eligibility_check_id')->constrained()->cascadeOnDelete();
            $table->string('snapshot_type', 80);
            $table->json('data');
            $table->timestamps();

            $table->unique(['eligibility_check_id', 'snapshot_type'], 'eligibility_snapshots_check_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_snapshots');
        Schema::dropIfExists('eligibility_check_results');
        Schema::dropIfExists('eligibility_checks');
        Schema::dropIfExists('eligibility_criteria');
        Schema::dropIfExists('eligibility_rule_sets');
    }
};
