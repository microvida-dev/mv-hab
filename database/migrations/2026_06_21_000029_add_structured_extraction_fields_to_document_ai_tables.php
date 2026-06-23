<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_ai_analyses', function (Blueprint $table): void {
            $table->string('extraction_status', 40)->nullable()->after('classified_at')->index();
            $table->string('extraction_schema_version', 40)->nullable()->after('extraction_status');
            $table->json('extraction_json')->nullable()->after('extraction_schema_version');
            $table->decimal('extraction_confidence', 5, 2)->nullable()->after('extraction_json');
            $table->string('extraction_model', 120)->nullable()->after('extraction_confidence');
            $table->string('extraction_prompt_version', 80)->nullable()->after('extraction_model');
            $table->timestamp('extraction_started_at')->nullable()->after('extraction_prompt_version');
            $table->timestamp('extraction_completed_at')->nullable()->after('extraction_started_at');
            $table->timestamp('extraction_failed_at')->nullable()->after('extraction_completed_at');
            $table->boolean('extraction_requires_manual_review')->default(false)->after('extraction_failed_at')->index();
        });

        Schema::table('document_ai_fields', function (Blueprint $table): void {
            $table->string('document_type', 80)->nullable()->after('document_ai_analysis_id')->index();
            $table->string('source', 80)->nullable()->after('confidence')->index();
            $table->boolean('requires_review')->default(false)->after('source')->index();
        });
    }

    public function down(): void
    {
        Schema::table('document_ai_fields', function (Blueprint $table): void {
            $table->dropColumn([
                'document_type',
                'source',
                'requires_review',
            ]);
        });

        Schema::table('document_ai_analyses', function (Blueprint $table): void {
            $table->dropColumn([
                'extraction_status',
                'extraction_schema_version',
                'extraction_json',
                'extraction_confidence',
                'extraction_model',
                'extraction_prompt_version',
                'extraction_started_at',
                'extraction_completed_at',
                'extraction_failed_at',
                'extraction_requires_manual_review',
            ]);
        });
    }
};
