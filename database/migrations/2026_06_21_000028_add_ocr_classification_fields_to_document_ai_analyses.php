<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_ai_analyses', function (Blueprint $table): void {
            $table->string('ocr_status', 40)->nullable()->after('failure_reason')->index();
            $table->boolean('ocr_available')->default(false)->after('ocr_status')->index();
            $table->string('ocr_engine', 120)->nullable()->after('ocr_available');
            $table->string('ocr_language', 40)->nullable()->after('ocr_engine');
            $table->longText('ocr_text')->nullable()->after('ocr_language');
            $table->decimal('ocr_quality_score', 5, 2)->nullable()->after('ocr_text');
            $table->unsignedInteger('ocr_pages_count')->nullable()->after('ocr_quality_score');
            $table->timestamp('ocr_processed_at')->nullable()->after('ocr_pages_count');
            $table->string('classification_status', 40)->nullable()->after('ocr_processed_at')->index();
            $table->string('detected_document_type', 80)->nullable()->after('classification_status')->index();
            $table->string('detected_document_label')->nullable()->after('detected_document_type');
            $table->decimal('classification_confidence', 5, 2)->nullable()->after('detected_document_label');
            $table->string('classification_source', 80)->nullable()->after('classification_confidence');
            $table->string('classification_model', 120)->nullable()->after('classification_source');
            $table->string('classification_prompt_version', 80)->nullable()->after('classification_model');
            $table->json('classification_signals')->nullable()->after('classification_prompt_version');
            $table->boolean('classification_requires_manual_review')->default(false)->after('classification_signals')->index();
            $table->timestamp('classified_at')->nullable()->after('classification_requires_manual_review')->index();
        });
    }

    public function down(): void
    {
        Schema::table('document_ai_analyses', function (Blueprint $table): void {
            $table->dropColumn([
                'ocr_status',
                'ocr_available',
                'ocr_engine',
                'ocr_language',
                'ocr_text',
                'ocr_quality_score',
                'ocr_pages_count',
                'ocr_processed_at',
                'classification_status',
                'detected_document_type',
                'detected_document_label',
                'classification_confidence',
                'classification_source',
                'classification_model',
                'classification_prompt_version',
                'classification_signals',
                'classification_requires_manual_review',
                'classified_at',
            ]);
        });
    }
};
