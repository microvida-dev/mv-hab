<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('application_number')->nullable()->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('adhesion_registration_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('contest_id')->constrained()->restrictOnDelete();
            $table->foreignId('household_id')->constrained()->restrictOnDelete();
            $table->foreignId('current_housing_situation_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->boolean('declaration_accepted')->default(false);
            $table->timestamp('declaration_accepted_at')->nullable();
            $table->boolean('contest_rules_accepted')->default(false);
            $table->timestamp('contest_rules_accepted_at')->nullable();
            $table->boolean('data_processing_accepted')->default(false);
            $table->timestamp('data_processing_accepted_at')->nullable();
            $table->boolean('truthfulness_accepted')->default(false);
            $table->timestamp('truthfulness_accepted_at')->nullable();
            $table->boolean('data_current_confirmed')->default(false);
            $table->timestamp('data_current_confirmed_at')->nullable();
            $table->text('candidate_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'contest_id', 'status'], 'applications_user_contest_status_idx');
            $table->index(['contest_id', 'submitted_at'], 'applications_contest_submitted_idx');
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('application_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('snapshot_type');
            $table->json('data');
            $table->timestamps();

            $table->unique(['application_id', 'snapshot_type'], 'application_snapshots_type_unique');
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_submission_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->string('status_at_submission');
            $table->timestamps();

            $table->unique(
                ['application_id', 'document_submission_id'],
                'application_documents_submission_unique',
            );
        });

        Schema::create('application_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('housing_unit_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('preference_order');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'housing_unit_id'], 'application_preferences_unit_unique');
            $table->unique(['application_id', 'preference_order'], 'application_preferences_order_unique');
        });

        Schema::create('application_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('declaration_type');
            $table->boolean('accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->string('text_version');
            $table->timestamps();

            $table->unique(['application_id', 'declaration_type'], 'application_declarations_type_unique');
        });

        Schema::table('document_submissions', function (Blueprint $table) {
            $table->foreign('application_id', 'document_submissions_application_fk')
                ->references('id')
                ->on('applications')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_submissions', function (Blueprint $table) {
            $table->dropForeign('document_submissions_application_fk');
        });

        Schema::dropIfExists('application_declarations');
        Schema::dropIfExists('application_preferences');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('application_snapshots');
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('applications');
    }
};
