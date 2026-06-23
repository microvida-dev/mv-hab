<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_ai_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_ai_analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('label', 80)->index();
            $table->json('components')->nullable();
            $table->json('explanation')->nullable();
            $table->string('summary')->nullable();
            $table->boolean('requires_manual_review')->default(false)->index();
            $table->timestamp('calculated_at')->nullable()->index();
            $table->timestamps();

            $table->unique('document_ai_analysis_id', 'document_ai_scores_analysis_unique');
            $table->index(['application_id', 'label'], 'document_ai_scores_application_label_idx');
            $table->index(['requires_manual_review', 'score'], 'document_ai_scores_review_score_idx');
        });

        Schema::create('document_ai_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_ai_analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_ai_score_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('flag_code', 120)->index();
            $table->string('severity', 40)->index();
            $table->string('status', 40)->default('draft')->index();
            $table->text('suggestion');
            $table->json('metadata')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('dismiss_reason')->nullable();
            $table->timestamps();

            $table->unique(['document_ai_analysis_id', 'flag_code'], 'document_ai_suggestion_analysis_flag_unique');
            $table->index(['application_id', 'status'], 'document_ai_suggestions_application_status_idx');
        });

        Schema::table('document_ai_flags', function (Blueprint $table): void {
            $table->smallInteger('score_impact')->nullable()->after('message');
            $table->string('suggestion_template', 120)->nullable()->after('score_impact');
            $table->string('detected_by', 120)->nullable()->after('suggestion_template');
            $table->decimal('confidence', 5, 2)->nullable()->after('detected_by');
        });
    }

    public function down(): void
    {
        Schema::table('document_ai_flags', function (Blueprint $table): void {
            $table->dropColumn([
                'score_impact',
                'suggestion_template',
                'detected_by',
                'confidence',
            ]);
        });

        Schema::dropIfExists('document_ai_suggestions');
        Schema::dropIfExists('document_ai_scores');
    }
};
