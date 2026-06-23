<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_number')->unique();
            $table->foreignId('application_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('preliminary_review_started_at')->nullable();
            $table->timestamp('document_review_started_at')->nullable();
            $table->timestamp('eligibility_review_started_at')->nullable();
            $table->timestamp('admitted_for_scoring_at')->nullable();
            $table->timestamp('not_admitted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('current_correction_request_id')->nullable();
            $table->text('summary')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('legal_basis')->nullable();
            $table->text('decision_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'assigned_to'], 'administrative_processes_status_assigned_idx');
            $table->index(['contest_id', 'status'], 'administrative_processes_contest_status_idx');
        });

        Schema::create('administrative_process_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrative_process_id');
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('administrative_process_id', 'ap_status_hist_process_fk')
                ->references('id')
                ->on('administrative_processes')
                ->cascadeOnDelete();
            $table->foreign('changed_by', 'ap_status_hist_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('application_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrative_process_id');
            $table->unsignedBigInteger('application_id');
            $table->string('review_type')->index();
            $table->string('status')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('result')->nullable()->index();
            $table->text('summary')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('administrative_process_id', 'app_reviews_process_fk')
                ->references('id')
                ->on('administrative_processes')
                ->cascadeOnDelete();
            $table->foreign('application_id', 'app_reviews_application_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('reviewed_by', 'app_reviews_reviewer_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('application_review_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_review_id');
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('category')->default('manual');
            $table->nullableMorphs('target');
            $table->string('result')->index();
            $table->text('message')->nullable();
            $table->text('technical_message')->nullable();
            $table->boolean('requires_correction')->default(false);
            $table->text('correction_reason')->nullable();
            $table->timestamps();

            $table->foreign('application_review_id', 'app_review_items_review_fk')
                ->references('id')
                ->on('application_reviews')
                ->cascadeOnDelete();
        });

        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrative_process_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->string('request_number')->unique();
            $table->string('status')->index();
            $table->string('subject');
            $table->text('message');
            $table->text('legal_basis')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('response_deadline_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('candidate_visible')->default(false);
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'candidate_visible', 'status'], 'correction_requests_candidate_visible_idx');
            $table->foreign('administrative_process_id', 'corr_requests_process_fk')
                ->references('id')
                ->on('administrative_processes')
                ->cascadeOnDelete();
            $table->foreign('application_id', 'corr_requests_application_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'corr_requests_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('issued_by', 'corr_requests_issuer_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('correction_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('correction_request_id');
            $table->nullableMorphs('target');
            $table->string('issue_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('required_action');
            $table->string('status')->index();
            $table->boolean('is_required')->default(true);
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->unsignedBigInteger('required_document_id')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('correction_request_id', 'corr_items_request_fk')
                ->references('id')
                ->on('correction_requests')
                ->cascadeOnDelete();
            $table->foreign('document_type_id', 'corr_items_doc_type_fk')
                ->references('id')
                ->on('document_types')
                ->nullOnDelete();
            $table->foreign('required_document_id', 'corr_items_required_doc_fk')
                ->references('id')
                ->on('required_documents')
                ->nullOnDelete();
        });

        Schema::create('correction_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('correction_request_id');
            $table->unsignedBigInteger('correction_request_item_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->text('response_text')->nullable();
            $table->unsignedBigInteger('document_submission_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_result')->nullable()->index();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status'], 'correction_responses_user_status_idx');
            $table->foreign('correction_request_id', 'corr_responses_request_fk')
                ->references('id')
                ->on('correction_requests')
                ->cascadeOnDelete();
            $table->foreign('correction_request_item_id', 'corr_responses_item_fk')
                ->references('id')
                ->on('correction_request_items')
                ->cascadeOnDelete();
            $table->foreign('application_id', 'corr_responses_application_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'corr_responses_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('document_submission_id', 'corr_responses_document_fk')
                ->references('id')
                ->on('document_submissions')
                ->nullOnDelete();
            $table->foreign('reviewed_by', 'corr_responses_reviewer_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('administrative_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrative_process_id');
            $table->unsignedBigInteger('application_id');
            $table->string('decision_type')->index();
            $table->string('decision_result')->index();
            $table->string('status')->index();
            $table->text('summary');
            $table->text('legal_basis')->nullable();
            $table->text('grounds');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('candidate_visible')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('administrative_process_id', 'admin_decisions_process_fk')
                ->references('id')
                ->on('administrative_processes')
                ->cascadeOnDelete();
            $table->foreign('application_id', 'admin_decisions_application_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('decided_by', 'admin_decisions_decider_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('approved_by', 'admin_decisions_approver_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('administrative_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrative_process_id');
            $table->unsignedBigInteger('application_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->string('priority')->index();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('administrative_process_id', 'admin_tasks_process_fk')
                ->references('id')
                ->on('administrative_processes')
                ->cascadeOnDelete();
            $table->foreign('application_id', 'admin_tasks_application_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('assigned_to', 'admin_tasks_assignee_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('created_by', 'admin_tasks_creator_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('administrative_process_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administrative_process_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('visibility')->default('internal')->index();
            $table->string('note_type')->default('general');
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('administrative_process_id', 'admin_notes_process_fk')
                ->references('id')
                ->on('administrative_processes')
                ->cascadeOnDelete();
            $table->foreign('application_id', 'admin_notes_application_fk')
                ->references('id')
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'admin_notes_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('administrative_workflow_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('default_correction_deadline_days')->default(10);
            $table->boolean('allow_deadline_extension')->default(false);
            $table->unsignedSmallInteger('max_deadline_extensions')->default(0);
            $table->boolean('auto_mark_overdue')->default(false);
            $table->boolean('requires_decision_approval')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contest_id', 'is_active'], 'administrative_configs_contest_active_idx');
            $table->index(['program_id', 'is_active'], 'administrative_configs_program_active_idx');
        });

        Schema::table('administrative_processes', function (Blueprint $table) {
            $table->foreign('current_correction_request_id', 'administrative_processes_current_request_fk')
                ->references('id')
                ->on('correction_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('administrative_processes', function (Blueprint $table) {
            $table->dropForeign('administrative_processes_current_request_fk');
        });

        Schema::dropIfExists('administrative_workflow_configs');
        Schema::dropIfExists('administrative_process_notes');
        Schema::dropIfExists('administrative_tasks');
        Schema::dropIfExists('administrative_decisions');
        Schema::dropIfExists('correction_responses');
        Schema::dropIfExists('correction_request_items');
        Schema::dropIfExists('correction_requests');
        Schema::dropIfExists('application_review_items');
        Schema::dropIfExists('application_reviews');
        Schema::dropIfExists('administrative_process_status_histories');
        Schema::dropIfExists('administrative_processes');
    }
};
