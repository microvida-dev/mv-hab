<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_timeline_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('adhesion_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('housing_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 100)->index();
            $table->string('visibility', 80)->index();
            $table->string('public_status', 100)->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->nullableMorphs('related');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['application_id', 'visibility', 'occurred_at'], 'timeline_app_visibility_date_idx');
            $table->index(['user_id', 'visibility', 'occurred_at'], 'timeline_user_visibility_date_idx');
        });

        Schema::create('application_public_status_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('public_status', 100)->index();
            $table->string('internal_status', 100)->nullable()->index();
            $table->string('title');
            $table->text('description');
            $table->text('next_step')->nullable();
            $table->boolean('action_required')->default(false);
            $table->timestamp('action_due_at')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->nullable();
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();
        });

        Schema::create('additional_document_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('required_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 80)->default('available')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['application_id', 'status'], 'add_doc_req_app_status_idx');
            $table->index(['user_id', 'status'], 'add_doc_req_user_status_idx');
        });

        Schema::create('additional_document_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('additional_document_request_id')->nullable();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('document_submission_id')->nullable();
            $table->string('status', 80)->default('submitted')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_disk')->default('local');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('additional_document_request_id', 'add_doc_sub_request_fk')->references('id')->on('additional_document_requests')->nullOnDelete();
            $table->foreign('document_submission_id', 'add_doc_sub_doc_fk')->references('id')->on('document_submissions')->nullOnDelete();
            $table->index(['application_id', 'status'], 'add_doc_sub_app_status_idx');
            $table->index(['user_id', 'status'], 'add_doc_sub_user_status_idx');
        });

        Schema::create('controlled_withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 80)->default('pending_confirmation')->index();
            $table->text('reason');
            $table->boolean('consequence_acknowledged')->default(false);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['application_id', 'status'], 'withdrawals_app_status_idx');
            $table->index(['user_id', 'status'], 'withdrawals_user_status_idx');
        });

        Schema::create('future_application_data_reuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('source_application_id')->nullable();
            $table->unsignedBigInteger('source_reuse_profile_id')->nullable();
            $table->unsignedBigInteger('target_application_id')->nullable();
            $table->string('status', 80)->default('requires_confirmation')->index();
            $table->json('sections')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('source_application_id', 'future_reuse_source_app_fk')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('source_reuse_profile_id', 'future_reuse_profile_fk')->references('id')->on('candidate_data_reuse_profiles')->nullOnDelete();
            $table->foreign('target_application_id', 'future_reuse_target_app_fk')->references('id')->on('applications')->nullOnDelete();
            $table->index(['user_id', 'status'], 'future_reuse_user_status_idx');
        });

        Schema::create('process_actions', function (Blueprint $table): void {
            $table->id();
            $table->string('action_number')->unique();
            $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 100)->index();
            $table->string('status', 80)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->nullableMorphs('related');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['application_id', 'status'], 'process_actions_app_status_idx');
            $table->index(['user_id', 'status'], 'process_actions_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_actions');
        Schema::dropIfExists('future_application_data_reuses');
        Schema::dropIfExists('controlled_withdrawals');
        Schema::dropIfExists('additional_document_submissions');
        Schema::dropIfExists('additional_document_requests');
        Schema::dropIfExists('application_public_status_snapshots');
        Schema::dropIfExists('process_timeline_events');
    }
};
