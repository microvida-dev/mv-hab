<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_ai_validation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('status', 40)->default('processing')->index();
            $table->unsignedInteger('total_checks')->default(0);
            $table->unsignedInteger('matches_count')->default(0);
            $table->unsignedInteger('critical_count')->default(0);
            $table->unsignedInteger('medium_count')->default(0);
            $table->unsignedInteger('light_count')->default(0);
            $table->unsignedInteger('inconclusive_count')->default(0);
            $table->boolean('requires_manual_review')->default(false)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['application_id', 'status'], 'doc_ai_validation_runs_app_status_idx');
            $table->index('created_at');
        });

        Schema::create('document_ai_validations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_ai_validation_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_ai_analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('document_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->string('validation_group', 80)->index();
            $table->string('validation_key', 120)->index();
            $table->string('label');
            $table->string('status', 60)->index();
            $table->string('severity', 80)->nullable()->index();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('candidate_value')->nullable();
            $table->text('extracted_value')->nullable();
            $table->string('candidate_value_hash', 128)->nullable();
            $table->string('extracted_value_hash', 128)->nullable();
            $table->string('value_type', 80)->nullable();
            $table->string('comparison_method', 80)->nullable();
            $table->string('message')->nullable();
            $table->string('recommendation')->nullable();
            $table->boolean('requires_manual_review')->default(false)->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'validation_group', 'severity'], 'doc_ai_validations_app_group_severity_idx');
            $table->index(['document_ai_analysis_id', 'validation_group'], 'doc_ai_validations_analysis_group_idx');
            $table->index(['requires_manual_review', 'created_at'], 'doc_ai_validations_review_created_idx');
            $table->unique(
                ['document_ai_analysis_id', 'application_id', 'validation_group', 'validation_key'],
                'doc_ai_validation_unique_check'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ai_validations');
        Schema::dropIfExists('document_ai_validation_runs');
    }
};
