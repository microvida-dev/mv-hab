<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_submission_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('documentable');
            $table->string('status', 40)->default('pending')->index();
            $table->string('engine', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('source_disk')->nullable();
            $table->text('source_path')->nullable();
            $table->string('source_mime')->nullable();
            $table->unsignedBigInteger('source_size_bytes')->nullable();
            $table->string('source_sha256', 128)->nullable()->index();
            $table->longText('raw_text')->nullable();
            $table->json('raw_ai_json')->nullable();
            $table->text('summary')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('manual_review_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['document_submission_id', 'document_version_id', 'status'], 'doc_ai_analysis_document_version_status_idx');
            $table->index('created_at');
        });

        Schema::create('document_ai_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_ai_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('key')->index();
            $table->string('label')->nullable();
            $table->text('value')->nullable();
            $table->text('normalized_value')->nullable();
            $table->string('value_type', 80)->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->unsignedInteger('page')->nullable();
            $table->json('bbox')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('document_ai_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_ai_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('code')->index();
            $table->string('severity', 40)->index();
            $table->string('message');
            $table->json('details')->nullable();
            $table->boolean('requires_manual_review')->default(false)->index();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('document_ai_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_ai_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('step')->index();
            $table->string('level', 40)->index();
            $table->string('message');
            $table->json('context')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ai_processing_logs');
        Schema::dropIfExists('document_ai_flags');
        Schema::dropIfExists('document_ai_fields');
        Schema::dropIfExists('document_ai_analyses');
    }
};
