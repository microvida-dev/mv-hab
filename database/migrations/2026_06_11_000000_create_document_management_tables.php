<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('applies_to');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required_by_default')->default(false);
            $table->boolean('requires_expiry_date')->default(false);
            $table->boolean('requires_issue_date')->default(false);
            $table->json('allowed_mime_types')->nullable();
            $table->unsignedSmallInteger('max_file_size_mb')->default(10);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('required_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('required_for');
            $table->string('condition_key')->default('always');
            $table->string('condition_operator')->default('always');
            $table->string('condition_value')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['required_for', 'is_active']);
            $table->index(['program_id', 'contest_id']);
        });

        Schema::create('document_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('required_document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('adhesion_registration_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('household_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('household_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('income_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('current_housing_situation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('submitted');
            $table->string('title')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('stored_filename')->nullable();
            $table->string('storage_disk')->default('local');
            $table->string('storage_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['adhesion_registration_id', 'status'], 'doc_submissions_registration_status_idx');
            $table->index(['document_type_id', 'required_document_id'], 'doc_submissions_type_required_idx');
            $table->index('household_member_id');
            $table->index('income_record_id');
            $table->index('current_housing_situation_id', 'doc_submissions_housing_situation_idx');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('checksum', 128);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('status_at_upload')->default('submitted');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['document_submission_id', 'version_number'], 'document_versions_submission_version_unique');
        });

        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('decision');
            $table->text('reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['document_submission_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('document_reviews');

        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('document_submissions');
        Schema::dropIfExists('required_documents');
        Schema::dropIfExists('document_types');
    }
};
