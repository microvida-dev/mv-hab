<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            $table->unsignedBigInteger('municipality_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('contest_id')->nullable()->after('municipality_id');
            $table->string('export_profile', 100)->nullable()->after('contest_id');
            $table->string('export_mode', 60)->nullable()->after('export_profile');
            $table->timestamp('snapshot_at')->nullable()->after('export_mode');
            $table->json('source_metadata')->nullable()->after('snapshot_at');
            $table->char('source_fingerprint', 64)->nullable()->after('source_metadata');
            $table->char('manifest_sha256', 64)->nullable()->after('source_fingerprint');
            $table->char('package_sha256', 64)->nullable()->after('manifest_sha256');
            $table->string('processing_stage', 60)->nullable()->after('package_sha256');
            $table->unsignedTinyInteger('progress')->default(0)->after('processing_stage');
            $table->timestamp('started_at')->nullable()->after('progress');
            $table->timestamp('failed_at')->nullable()->after('started_at');
            $table->string('failure_code', 120)->nullable()->after('failed_at');
            $table->char('idempotency_key', 64)->nullable()->after('failure_code');
            $table->json('formats')->nullable()->after('idempotency_key');
            $table->json('datasets')->nullable()->after('formats');
            $table->boolean('sensitive_fields_included')->default(false)->after('datasets');
            $table->boolean('document_files_requested')->default(false)->after('sensitive_fields_included');
            $table->boolean('document_files_included')->default(false)->after('document_files_requested');

            $table->foreign('municipality_id', 're_municipality_fk')
                ->references('id')
                ->on('municipalities')
                ->restrictOnDelete();
            $table->foreign('contest_id', 're_contest_fk')
                ->references('id')
                ->on('contests')
                ->restrictOnDelete();
            $table->index(
                ['municipality_id', 'export_profile', 'created_at'],
                're_municipality_profile_created_idx',
            );
            $table->index('contest_id', 're_contest_idx');
            $table->index('source_fingerprint', 're_source_fingerprint_idx');
            $table->unique('idempotency_key', 're_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        if (
            Schema::hasColumn('report_exports', 'export_profile')
            && DB::table('report_exports')->whereNotNull('export_profile')->exists()
        ) {
            throw new RuntimeException(
                'O rollback foi recusado porque existem exportações temporais com metadata que seria perdida.',
            );
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('report_exports', function (Blueprint $table): void {
                $table->dropForeign(['municipality_id']);
                $table->dropForeign(['contest_id']);
            });
        } else {
            Schema::table('report_exports', function (Blueprint $table): void {
                $table->dropForeign('re_municipality_fk');
                $table->dropForeign('re_contest_fk');
            });
        }

        Schema::table('report_exports', function (Blueprint $table): void {
            $table->dropIndex('re_municipality_profile_created_idx');
            $table->dropIndex('re_contest_idx');
            $table->dropIndex('re_source_fingerprint_idx');
            $table->dropUnique('re_idempotency_key_unique');

            $table->dropColumn([
                'municipality_id',
                'contest_id',
                'export_profile',
                'export_mode',
                'snapshot_at',
                'source_metadata',
                'source_fingerprint',
                'manifest_sha256',
                'package_sha256',
                'processing_stage',
                'progress',
                'started_at',
                'failed_at',
                'failure_code',
                'idempotency_key',
                'formats',
                'datasets',
                'sensitive_fields_included',
                'document_files_requested',
                'document_files_included',
            ]);
        });
    }
};
