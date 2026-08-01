<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite exige que PRAGMA foreign_keys seja alterado fora de uma
     * transação durante a reconstrução reversível da tabela.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('correction_requests', function (Blueprint $table): void {
            $table->foreignId('application_review_publication_result_id')
                ->nullable()
                ->after('id');
            $table->char('source_snapshot_hash', 64)
                ->nullable()
                ->after('application_review_publication_result_id');
            $table->timestamp('notified_at')->nullable()->after('issued_at');
            $table->timestamp('opened_at')->nullable()->after('notified_at');
            $table->timestamp('submitted_at')->nullable()->after('responded_at');
            $table->timestamp('expired_at')->nullable()->after('submitted_at');
            $table->timestamp('resolved_at')->nullable()->after('expired_at');

            $table->unique(
                'application_review_publication_result_id',
                'corr_requests_publication_result_uq',
            );
            $table->index(
                ['status', 'response_deadline_at'],
                'corr_requests_status_deadline_idx',
            );
            $table->foreign(
                'application_review_publication_result_id',
                'corr_requests_publication_result_fk',
            )->references('id')
                ->on('application_review_publication_results')
                ->restrictOnDelete();
        });

        Schema::create('correction_request_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipality_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();

            $table->unique(
                ['municipality_id', 'year'],
                'corr_request_sequences_municipality_year_uq',
            );
            $table->foreign(
                'municipality_id',
                'corr_request_sequences_municipality_fk',
            )->references('id')->on('municipalities')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_request_sequences');

        if (DB::getDriverName() === 'sqlite') {
            $this->rollbackSqliteCorrectionRequests();

            return;
        }

        Schema::table('correction_requests', function (Blueprint $table): void {
            $table->dropForeign('corr_requests_publication_result_fk');
            $table->dropUnique('corr_requests_publication_result_uq');
            $table->dropIndex('corr_requests_status_deadline_idx');
            $table->dropColumn([
                'application_review_publication_result_id',
                'source_snapshot_hash',
                'notified_at',
                'opened_at',
                'submitted_at',
                'expired_at',
                'resolved_at',
            ]);
        });
    }

    private function rollbackSqliteCorrectionRequests(): void
    {
        $temporaryTable = 'correction_requests_53e_rollback';

        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists($temporaryTable);

            Schema::create($temporaryTable, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('administrative_process_id');
                $table->unsignedBigInteger('application_id');
                $table->unsignedBigInteger('user_id');
                $table->string('request_number');
                $table->string('status');
                $table->string('subject');
                $table->text('message');
                $table->text('legal_basis')->nullable();
                $table->text('instructions')->nullable();
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('response_deadline_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->boolean('candidate_visible')->default(false);
                $table->text('internal_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign(
                    'administrative_process_id',
                    'corr_requests_process_fk',
                )->references('id')
                    ->on('administrative_processes')
                    ->cascadeOnDelete();
                $table->foreign(
                    'application_id',
                    'corr_requests_application_fk',
                )->references('id')
                    ->on('applications')
                    ->cascadeOnDelete();
                $table->foreign(
                    'user_id',
                    'corr_requests_user_fk',
                )->references('id')
                    ->on('users')
                    ->restrictOnDelete();
                $table->foreign(
                    'issued_by',
                    'corr_requests_issuer_fk',
                )->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });

            $columns = [
                'id',
                'administrative_process_id',
                'application_id',
                'user_id',
                'request_number',
                'status',
                'subject',
                'message',
                'legal_basis',
                'instructions',
                'issued_by',
                'issued_at',
                'response_deadline_at',
                'responded_at',
                'closed_at',
                'cancelled_at',
                'candidate_visible',
                'internal_notes',
                'created_at',
                'updated_at',
                'deleted_at',
            ];

            DB::table($temporaryTable)->insertUsing(
                $columns,
                DB::table('correction_requests')->select($columns),
            );

            Schema::drop('correction_requests');
            Schema::rename($temporaryTable, 'correction_requests');

            Schema::table('correction_requests', function (Blueprint $table): void {
                $table->unique(
                    'request_number',
                    'correction_requests_request_number_unique',
                );
                $table->index(
                    'status',
                    'correction_requests_status_index',
                );
                $table->index(
                    ['user_id', 'candidate_visible', 'status'],
                    'correction_requests_candidate_visible_idx',
                );
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
