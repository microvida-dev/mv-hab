<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_review_batches', function (Blueprint $table): void {
            $table->foreignId('correction_request_id')
                ->nullable()
                ->after('contest_id');
            $table->char('collective_scope_key', 64)
                ->nullable()
                ->after('correction_request_id');

            $table->foreign(
                'correction_request_id',
                'review_batches_correction_request_fk',
            )->references('id')
                ->on('correction_requests')
                ->restrictOnDelete();
            $table->unique(
                'correction_request_id',
                'review_batches_correction_request_uq',
            );
            $table->unique(
                'collective_scope_key',
                'review_batches_collective_scope_uq',
            );
        });

        DB::table('application_review_batches')
            ->orderBy('id')
            ->get(['id', 'contest_id', 'cycle'])
            ->each(function (object $batch): void {
                DB::table('application_review_batches')
                    ->where('id', $batch->id)
                    ->update([
                        'collective_scope_key' => hash(
                            'sha256',
                            'contest:'.$batch->contest_id
                                .':cycle:'.$batch->cycle,
                        ),
                    ]);
            });

        Schema::table('application_review_batches', function (Blueprint $table): void {
            $table->dropUnique('review_batches_contest_cycle_unique');
        });

        Schema::table('correction_requests', function (Blueprint $table): void {
            $table->foreignId('revalidation_started_by')
                ->nullable()
                ->after('issued_by');
            $table->timestamp('revalidation_started_at')
                ->nullable()
                ->after('submitted_at');
            $table->string('revalidation_result', 40)
                ->nullable()
                ->after('revalidation_started_at');
            $table->foreignId('revalidation_publication_result_id')
                ->nullable()
                ->after('revalidation_result');
            $table->foreignId('revalidation_projected_by')
                ->nullable()
                ->after('revalidation_publication_result_id');
            $table->timestamp('revalidation_projected_at')
                ->nullable()
                ->after('revalidation_projected_by');

            $table->foreign(
                'revalidation_started_by',
                'corr_requests_revalidation_starter_fk',
            )->references('id')->on('users')->nullOnDelete();
            $table->foreign(
                'revalidation_publication_result_id',
                'corr_requests_revalidation_result_fk',
            )->references('id')
                ->on('application_review_publication_results')
                ->restrictOnDelete();
            $table->foreign(
                'revalidation_projected_by',
                'corr_requests_revalidation_projector_fk',
            )->references('id')->on('users')->nullOnDelete();
            $table->unique(
                'revalidation_publication_result_id',
                'corr_requests_revalidation_result_uq',
            );
            $table->index(
                [
                    'status',
                    'revalidation_started_at',
                    'revalidation_result',
                ],
                'corr_requests_revalidation_queue_idx',
            );
        });

        Schema::table('correction_responses', function (Blueprint $table): void {
            $table->string('differential_classification', 40)
                ->nullable()
                ->after('review_result');
            $table->char('decision_source_fingerprint', 64)
                ->nullable()
                ->after('differential_classification');

            $table->index(
                ['correction_request_id', 'review_result'],
                'corr_responses_request_review_result_idx',
            );
        });
    }

    public function down(): void
    {
        $duplicateCollectiveCycles = DB::table('application_review_batches')
            ->select(['contest_id', 'cycle'])
            ->groupBy(['contest_id', 'cycle'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateCollectiveCycles) {
            throw new RuntimeException(
                'O rollback 53F não pode restaurar a unicidade por concurso/ciclo sem eliminar lotes de revalidação. Remova primeiro os dados funcionais 53F num ambiente controlado.',
            );
        }

        Schema::table('correction_responses', function (Blueprint $table): void {
            $table->dropIndex(
                'corr_responses_request_review_result_idx',
            );
            $table->dropColumn([
                'differential_classification',
                'decision_source_fingerprint',
            ]);
        });

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('correction_requests', function (Blueprint $table): void {
                $table->dropForeign([
                    'revalidation_projected_by',
                ]);
                $table->dropForeign([
                    'revalidation_publication_result_id',
                ]);
                $table->dropForeign([
                    'revalidation_started_by',
                ]);
            });
        } else {
            Schema::table('correction_requests', function (Blueprint $table): void {
                $table->dropForeign(
                    'corr_requests_revalidation_projector_fk',
                );
                $table->dropForeign(
                    'corr_requests_revalidation_result_fk',
                );
                $table->dropForeign(
                    'corr_requests_revalidation_starter_fk',
                );
            });
        }

        Schema::table('correction_requests', function (Blueprint $table): void {
            $table->dropUnique(
                'corr_requests_revalidation_result_uq',
            );
            $table->dropIndex(
                'corr_requests_revalidation_queue_idx',
            );
            $table->dropColumn([
                'revalidation_started_by',
                'revalidation_started_at',
                'revalidation_result',
                'revalidation_publication_result_id',
                'revalidation_projected_by',
                'revalidation_projected_at',
            ]);
        });

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('application_review_batches', function (Blueprint $table): void {
                $table->dropForeign([
                    'correction_request_id',
                ]);
            });
        } else {
            Schema::table('application_review_batches', function (Blueprint $table): void {
                $table->dropForeign(
                    'review_batches_correction_request_fk',
                );
            });
        }

        Schema::table('application_review_batches', function (Blueprint $table): void {
            $table->dropUnique(
                'review_batches_correction_request_uq',
            );
            $table->dropUnique(
                'review_batches_collective_scope_uq',
            );
            $table->dropColumn([
                'correction_request_id',
                'collective_scope_key',
            ]);
            $table->unique(
                ['contest_id', 'cycle'],
                'review_batches_contest_cycle_unique',
            );
        });
    }
};
