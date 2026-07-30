<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'application_reviews',
            function (Blueprint $table): void {
                $table->timestamp('ready_for_closure_at')
                    ->nullable()
                    ->after('completed_at')
                    ->index();
                $table->foreignId('ready_for_closure_by')
                    ->nullable()
                    ->after('ready_for_closure_at')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('last_activity_at')
                    ->nullable()
                    ->after('ready_for_closure_by')
                    ->index();
                $table->unsignedInteger('lock_version')
                    ->default(0)
                    ->after('last_activity_at');

                $table->index(
                    [
                        'administrative_process_id',
                        'review_type',
                        'status',
                    ],
                    'app_reviews_process_type_status_idx',
                );
                $table->index(
                    ['application_id', 'status'],
                    'app_reviews_application_status_idx',
                );
            },
        );

        Schema::table(
            'administrative_processes',
            function (Blueprint $table): void {
                $table->index(
                    ['contest_id', 'assigned_to', 'status'],
                    'admin_processes_contest_assigned_status_idx',
                );
            },
        );

        Schema::table(
            'document_submissions',
            function (Blueprint $table): void {
                $table->index(
                    ['application_id', 'status'],
                    'doc_submissions_application_status_idx',
                );
            },
        );
    }

    public function down(): void
    {
        /*
         * MySQL/MariaDB may discard an implicit single-column index after a
         * broader composite index is created and then use the composite index
         * to enforce the foreign key. Recreate the canonical supporting
         * indexes before dropping the Sprint 53B indexes so rollback remains
         * deterministic and does not require dropping foreign keys.
         */
        $this->ensureMysqlForeignKeyIndex(
            'document_submissions',
            'document_submissions_application_fk',
            ['application_id'],
        );
        $this->ensureMysqlForeignKeyIndex(
            'application_reviews',
            'app_reviews_process_fk',
            ['administrative_process_id'],
        );
        $this->ensureMysqlForeignKeyIndex(
            'application_reviews',
            'app_reviews_application_fk',
            ['application_id'],
        );

        Schema::table(
            'document_submissions',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'doc_submissions_application_status_idx',
                );
            },
        );

        Schema::table(
            'administrative_processes',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'admin_processes_contest_assigned_status_idx',
                );
            },
        );

        Schema::table(
            'application_reviews',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'app_reviews_process_type_status_idx',
                );
                $table->dropIndex(
                    'app_reviews_application_status_idx',
                );
                $table->dropIndex(
                    'application_reviews_ready_for_closure_at_index',
                );
                $table->dropIndex(
                    'application_reviews_last_activity_at_index',
                );
                $table->dropConstrainedForeignId(
                    'ready_for_closure_by',
                );
                $table->dropColumn([
                    'ready_for_closure_at',
                    'last_activity_at',
                    'lock_version',
                ]);
            },
        );
    }

    /**
     * @param  list<string>  $columns
     */
    private function ensureMysqlForeignKeyIndex(
        string $tableName,
        string $indexName,
        array $columns,
    ): void {
        if (
            DB::connection()->getDriverName() !== 'mysql'
            || Schema::hasIndex($tableName, $indexName)
        ) {
            return;
        }

        Schema::table(
            $tableName,
            function (Blueprint $table) use (
                $columns,
                $indexName,
            ): void {
                $table->index($columns, $indexName);
            },
        );
    }
};
