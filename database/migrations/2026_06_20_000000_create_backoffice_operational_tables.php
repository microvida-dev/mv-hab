<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backoffice_dashboard_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('snapshot_number')->unique();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('metrics');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index('contest_id');
            $table->index('generated_at');
        });

        Schema::create('application_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('report_number')->unique();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->string('format');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('payload');
            $table->string('file_path')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable()->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('application_id');
            $table->index('status');
        });

        Schema::create('document_dossiers', function (Blueprint $table): void {
            $table->id();
            $table->string('dossier_number')->unique();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('standardization_payload');
            $table->unsignedInteger('missing_documents_count')->default(0);
            $table->unsignedInteger('rejected_documents_count')->default(0);
            $table->unsignedInteger('expired_documents_count')->default(0);
            $table->unsignedInteger('validated_documents_count')->default(0);
            $table->string('file_path')->nullable();
            $table->timestamp('standardized_at')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('application_id');
            $table->index('status');
        });

        Schema::create('document_dossier_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_dossier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('required_document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->index();
            $table->string('label');
            $table->string('status')->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_missing')->default(false);
            $table->boolean('is_rejected')->default(false);
            $table->boolean('is_expired')->default(false);
            $table->boolean('is_validated')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('internal_alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('alert_number')->unique();
            $table->string('type')->index();
            $table->string('severity')->index();
            $table->string('status')->index();
            $table->string('title');
            $table->text('message');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assigned_role')->nullable()->index();
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('related');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('procedure_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('template_number')->unique();
            $table->string('type')->index();
            $table->string('status')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1)->index();
            $table->longText('content');
            $table->json('variables')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('superseded_by')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('generated_procedure_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number')->unique();
            $table->foreignId('procedure_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('status')->index();
            $table->string('title');
            $table->string('format');
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->json('payload');
            $table->longText('content_snapshot');
            $table->string('file_path')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('list_automation_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('run_number')->unique();
            $table->foreignId('contest_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('status')->index();
            $table->foreignId('source_ranking_snapshot_id')->nullable()->constrained('ranking_snapshots')->nullOnDelete();
            $table->foreignId('source_provisional_list_id')->nullable()->constrained('provisional_lists')->nullOnDelete();
            $table->foreignId('source_definitive_list_id')->nullable()->constrained('definitive_lists')->nullOnDelete();
            $table->unsignedInteger('total_candidates')->default(0);
            $table->unsignedInteger('included_count')->default(0);
            $table->unsignedInteger('excluded_count')->default(0);
            $table->unsignedInteger('warnings_count')->default(0);
            $table->json('criteria_snapshot');
            $table->json('result_payload');
            $table->string('file_path')->nullable();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('generated_at')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('contest_id');
        });

        Schema::create('procedure_minutes', function (Blueprint $table): void {
            $table->id();
            $table->string('minute_number')->unique();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('procedure_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->index();
            $table->string('title');
            $table->date('meeting_date')->nullable();
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->longText('content_snapshot');
            $table->json('payload');
            $table->string('file_path')->nullable();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('generated_at')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('process_confirmations', function (Blueprint $table): void {
            $table->id();
            $table->string('confirmation_number')->unique();
            $table->string('process_number')->unique();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->index();
            $table->string('title');
            $table->text('message');
            $table->json('payload');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('application_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_confirmations');
        Schema::dropIfExists('procedure_minutes');
        Schema::dropIfExists('list_automation_runs');
        Schema::dropIfExists('generated_procedure_documents');
        Schema::dropIfExists('procedure_templates');
        Schema::dropIfExists('internal_alerts');
        Schema::dropIfExists('document_dossier_items');
        Schema::dropIfExists('document_dossiers');
        Schema::dropIfExists('application_reports');
        Schema::dropIfExists('backoffice_dashboard_snapshots');
    }
};
