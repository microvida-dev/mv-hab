<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provisional_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('ranking_snapshot_id');
            $table->unsignedBigInteger('scoring_run_id')->nullable();
            $table->string('list_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedInteger('version_number')->default(1);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('publication_starts_at')->nullable();
            $table->timestamp('publication_ends_at')->nullable();
            $table->timestamp('complaint_period_starts_at')->nullable();
            $table->timestamp('complaint_period_ends_at')->nullable();
            $table->string('anonymization_mode', 80)->default('public_identifier_only');
            $table->boolean('public_visibility')->default(false);
            $table->text('internal_notes')->nullable();
            $table->text('legal_basis')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'prov_lists_program_fk')->references('id')->on('programs')->restrictOnDelete();
            $table->foreign('contest_id', 'prov_lists_contest_fk')->references('id')->on('contests')->restrictOnDelete();
            $table->foreign('ranking_snapshot_id', 'prov_lists_snapshot_fk')->references('id')->on('ranking_snapshots')->restrictOnDelete();
            $table->foreign('scoring_run_id', 'prov_lists_run_fk')->references('id')->on('scoring_runs')->restrictOnDelete();
            $table->foreign('generated_by', 'prov_lists_generator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by', 'prov_lists_reviewer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'prov_lists_approver_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('published_by', 'prov_lists_publisher_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'prov_lists_contest_status_idx');
            $table->index(['program_id', 'status'], 'prov_lists_program_status_idx');
        });

        Schema::create('provisional_list_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provisional_list_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('application_score_id')->nullable();
            $table->unsignedBigInteger('ranking_entry_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('entry_type', 60)->index();
            $table->string('status', 80)->index();
            $table->unsignedInteger('rank_position')->nullable();
            $table->decimal('total_score', 12, 2)->nullable();
            $table->string('public_identifier')->index();
            $table->string('candidate_name_masked')->nullable();
            $table->string('application_number_masked')->nullable();
            $table->text('exclusion_reason')->nullable();
            $table->text('exclusion_legal_basis')->nullable();
            $table->text('decision_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provisional_list_id', 'prov_entries_list_fk')->references('id')->on('provisional_lists')->cascadeOnDelete();
            $table->foreign('application_id', 'prov_entries_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('application_score_id', 'prov_entries_score_fk')->references('id')->on('application_scores')->nullOnDelete();
            $table->foreign('ranking_entry_id', 'prov_entries_rank_fk')->references('id')->on('ranking_entries')->nullOnDelete();
            $table->foreign('user_id', 'prov_entries_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['provisional_list_id', 'application_id'], 'prov_entries_list_application_unique');
            $table->unique(['provisional_list_id', 'public_identifier'], 'prov_entries_list_public_unique');
            $table->index(['provisional_list_id', 'rank_position'], 'prov_entries_list_rank_idx');
        });

        Schema::create('definitive_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('contest_id')->nullable();
            $table->unsignedBigInteger('provisional_list_id');
            $table->unsignedBigInteger('ranking_snapshot_id')->nullable();
            $table->unsignedBigInteger('scoring_run_id')->nullable();
            $table->string('list_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedInteger('version_number')->default(1);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('publication_starts_at')->nullable();
            $table->timestamp('publication_ends_at')->nullable();
            $table->string('anonymization_mode', 80)->default('public_identifier_only');
            $table->boolean('public_visibility')->default(false);
            $table->text('internal_notes')->nullable();
            $table->text('legal_basis')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_id', 'def_lists_program_fk')->references('id')->on('programs')->restrictOnDelete();
            $table->foreign('contest_id', 'def_lists_contest_fk')->references('id')->on('contests')->restrictOnDelete();
            $table->foreign('provisional_list_id', 'def_lists_provisional_fk')->references('id')->on('provisional_lists')->restrictOnDelete();
            $table->foreign('ranking_snapshot_id', 'def_lists_snapshot_fk')->references('id')->on('ranking_snapshots')->restrictOnDelete();
            $table->foreign('scoring_run_id', 'def_lists_run_fk')->references('id')->on('scoring_runs')->restrictOnDelete();
            $table->foreign('generated_by', 'def_lists_generator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by', 'def_lists_reviewer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'def_lists_approver_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('published_by', 'def_lists_publisher_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['contest_id', 'status'], 'def_lists_contest_status_idx');
            $table->index(['program_id', 'status'], 'def_lists_program_status_idx');
        });

        Schema::create('definitive_list_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('definitive_list_id');
            $table->unsignedBigInteger('provisional_list_entry_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('application_score_id')->nullable();
            $table->unsignedBigInteger('ranking_entry_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('entry_type', 60)->index();
            $table->string('status', 80)->index();
            $table->unsignedInteger('rank_position')->nullable();
            $table->unsignedInteger('previous_rank_position')->nullable();
            $table->decimal('total_score', 12, 2)->nullable();
            $table->decimal('previous_total_score', 12, 2)->nullable();
            $table->string('public_identifier')->index();
            $table->string('candidate_name_masked')->nullable();
            $table->string('application_number_masked')->nullable();
            $table->text('exclusion_reason')->nullable();
            $table->text('exclusion_legal_basis')->nullable();
            $table->text('decision_summary')->nullable();
            $table->text('change_reason')->nullable();
            $table->boolean('changed_after_complaint')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('definitive_list_id', 'def_entries_list_fk')->references('id')->on('definitive_lists')->cascadeOnDelete();
            $table->foreign('provisional_list_entry_id', 'def_entries_prov_entry_fk')->references('id')->on('provisional_list_entries')->nullOnDelete();
            $table->foreign('application_id', 'def_entries_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('application_score_id', 'def_entries_score_fk')->references('id')->on('application_scores')->nullOnDelete();
            $table->foreign('ranking_entry_id', 'def_entries_rank_fk')->references('id')->on('ranking_entries')->nullOnDelete();
            $table->foreign('user_id', 'def_entries_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['definitive_list_id', 'application_id'], 'def_entries_list_application_unique');
            $table->index(['definitive_list_id', 'rank_position'], 'def_entries_list_rank_idx');
        });

        Schema::create('list_publications', function (Blueprint $table) {
            $table->id();
            $table->morphs('publishable');
            $table->string('publication_type', 80)->index();
            $table->string('status', 80)->default('draft')->index();
            $table->string('channel', 80)->default('candidate_area')->index();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('public_url')->nullable();
            $table->string('internal_url')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('unpublished_by')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            $table->timestamp('visibility_starts_at')->nullable();
            $table->timestamp('visibility_ends_at')->nullable();
            $table->string('anonymization_mode', 80)->default('public_identifier_only');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('published_by', 'list_pubs_publisher_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('unpublished_by', 'list_pubs_unpublisher_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['publication_type', 'status', 'channel'], 'list_pubs_type_status_channel_idx');
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provisional_list_id');
            $table->unsignedBigInteger('provisional_list_entry_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->string('complaint_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('subject');
            $table->text('grounds');
            $table->text('requested_outcome')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('review_completed_at')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->boolean('requires_additional_information')->default(false);
            $table->timestamp('additional_information_requested_at')->nullable();
            $table->timestamp('additional_information_deadline_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('candidate_visible')->default(true);
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provisional_list_id', 'complaints_prov_list_fk')->references('id')->on('provisional_lists')->restrictOnDelete();
            $table->foreign('provisional_list_entry_id', 'complaints_prov_entry_fk')->references('id')->on('provisional_list_entries')->nullOnDelete();
            $table->foreign('application_id', 'complaints_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'complaints_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to', 'complaints_assignee_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['provisional_list_id', 'status'], 'complaints_list_status_idx');
            $table->index(['user_id', 'status'], 'complaints_user_status_idx');
        });

        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('document_submission_id')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('complaint_id', 'complaint_attach_complaint_fk')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('document_submission_id', 'complaint_attach_doc_fk')->references('id')->on('document_submissions')->nullOnDelete();
            $table->foreign('uploaded_by', 'complaint_attach_uploader_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('complaint_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->string('result', 100)->nullable()->index();
            $table->text('summary')->nullable();
            $table->text('technical_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('complaint_id', 'complaint_reviews_complaint_fk')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('reviewed_by', 'complaint_reviews_reviewer_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('complaint_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('provisional_list_id');
            $table->string('decision_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('decision_result', 100)->index();
            $table->text('summary');
            $table->text('grounds');
            $table->text('legal_basis')->nullable();
            $table->text('effects_on_ranking')->nullable();
            $table->text('effects_on_exclusion')->nullable();
            $table->boolean('requires_list_update')->default(false);
            $table->unsignedBigInteger('proposed_by')->nullable();
            $table->timestamp('proposed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->boolean('candidate_visible')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('complaint_id', 'complaint_decisions_complaint_fk')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('application_id', 'complaint_decisions_app_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('provisional_list_id', 'complaint_decisions_list_fk')->references('id')->on('provisional_lists')->restrictOnDelete();
            $table->foreign('proposed_by', 'complaint_decisions_proposer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by', 'complaint_decisions_approver_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('additional_information_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->string('request_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('subject');
            $table->text('message');
            $table->text('instructions')->nullable();
            $table->timestamp('deadline_at');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('complaint_id', 'info_requests_complaint_fk')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('application_id', 'info_requests_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'info_requests_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('issued_by', 'info_requests_issuer_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('additional_information_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('additional_information_request_id');
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->text('response_text')->nullable();
            $table->unsignedBigInteger('document_submission_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_result', 100)->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('additional_information_request_id', 'info_responses_request_fk')->references('id')->on('additional_information_requests')->cascadeOnDelete();
            $table->foreign('complaint_id', 'info_responses_complaint_fk')->references('id')->on('complaints')->cascadeOnDelete();
            $table->foreign('application_id', 'info_responses_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'info_responses_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('document_submission_id', 'info_responses_doc_fk')->references('id')->on('document_submissions')->nullOnDelete();
            $table->foreign('reviewed_by', 'info_responses_reviewer_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('hearings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provisional_list_id')->nullable();
            $table->unsignedBigInteger('definitive_list_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->string('hearing_number')->unique();
            $table->string('status', 80)->default('draft')->index();
            $table->string('hearing_type', 100)->index();
            $table->string('subject');
            $table->text('message');
            $table->text('legal_basis')->nullable();
            $table->text('grounds');
            $table->timestamp('deadline_at');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('candidate_visible')->default(false);
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provisional_list_id', 'hearings_prov_list_fk')->references('id')->on('provisional_lists')->nullOnDelete();
            $table->foreign('definitive_list_id', 'hearings_def_list_fk')->references('id')->on('definitive_lists')->nullOnDelete();
            $table->foreign('application_id', 'hearings_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'hearings_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('issued_by', 'hearings_issuer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by', 'hearings_reviewer_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('hearing_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hearing_id');
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->text('submission_text');
            $table->unsignedBigInteger('document_submission_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 80)->default('draft')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_result', 100)->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('hearing_id', 'hearing_submissions_hearing_fk')->references('id')->on('hearings')->cascadeOnDelete();
            $table->foreign('application_id', 'hearing_submissions_app_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'hearing_submissions_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('document_submission_id', 'hearing_submissions_doc_fk')->references('id')->on('document_submissions')->nullOnDelete();
            $table->foreign('reviewed_by', 'hearing_submissions_reviewer_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('official_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->nullableMorphs('notifiable');
            $table->string('notification_type', 100)->index();
            $table->string('status', 80)->default('draft')->index();
            $table->string('channel', 80)->default('candidate_area')->index();
            $table->string('subject');
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id', 'official_notifications_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('application_id', 'official_notifications_app_fk')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('created_by', 'official_notifications_creator_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('list_change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provisional_list_id')->nullable();
            $table->unsignedBigInteger('definitive_list_id')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('change_type', 100)->index();
            $table->text('from_value')->nullable();
            $table->text('to_value')->nullable();
            $table->text('reason')->nullable();
            $table->nullableMorphs('source');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('provisional_list_id', 'list_changes_prov_list_fk')->references('id')->on('provisional_lists')->nullOnDelete();
            $table->foreign('definitive_list_id', 'list_changes_def_list_fk')->references('id')->on('definitive_lists')->nullOnDelete();
            $table->foreign('application_id', 'list_changes_application_fk')->references('id')->on('applications')->restrictOnDelete();
            $table->foreign('user_id', 'list_changes_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('changed_by', 'list_changes_actor_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_change_logs');
        Schema::dropIfExists('official_notifications');
        Schema::dropIfExists('hearing_submissions');
        Schema::dropIfExists('hearings');
        Schema::dropIfExists('additional_information_responses');
        Schema::dropIfExists('additional_information_requests');
        Schema::dropIfExists('complaint_decisions');
        Schema::dropIfExists('complaint_reviews');
        Schema::dropIfExists('complaint_attachments');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('list_publications');
        Schema::dropIfExists('definitive_list_entries');
        Schema::dropIfExists('definitive_lists');
        Schema::dropIfExists('provisional_list_entries');
        Schema::dropIfExists('provisional_lists');
    }
};
