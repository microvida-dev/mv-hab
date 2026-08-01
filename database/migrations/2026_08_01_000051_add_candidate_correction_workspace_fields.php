<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicates = DB::table('correction_responses')
            ->select([
                'correction_request_id',
                'correction_request_item_id',
                'user_id',
            ])
            ->groupBy([
                'correction_request_id',
                'correction_request_item_id',
                'user_id',
            ])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException(
                'Existem respostas duplicadas por pedido, item e candidato. Regularize os dados antes de aplicar a migration 53E-B.',
            );
        }

        Schema::table(
            'correction_request_items',
            function (Blueprint $table): void {
                $table->unsignedSmallInteger('requirement_instance')
                    ->default(1)
                    ->after('required_document_id');
                $table->unsignedBigInteger(
                    'source_document_submission_id',
                )->nullable()->after('requirement_instance');

            },
        );

        Schema::table(
            'correction_responses',
            function (Blueprint $table): void {
                $table->string('response_kind', 32)
                    ->nullable()
                    ->after('response_text');
                $table->unsignedBigInteger('document_version_id')
                    ->nullable()
                    ->after('document_submission_id');
                $table->timestamp('prepared_at')
                    ->nullable()
                    ->after('document_version_id');

                $table->unique(
                    [
                        'correction_request_id',
                        'correction_request_item_id',
                        'user_id',
                    ],
                    'corr_responses_request_item_user_uq',
                );
            },
        );

        Schema::table(
            'document_versions',
            function (Blueprint $table): void {
                $table->unsignedBigInteger(
                    'replaces_document_version_id',
                )->nullable()->after('document_submission_id');

            },
        );

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table(
                'correction_request_items',
                function (Blueprint $table): void {
                    $table->foreign(
                        'source_document_submission_id',
                        'corr_items_source_document_fk',
                    )->references('id')
                        ->on('document_submissions')
                        ->restrictOnDelete();
                },
            );

            Schema::table(
                'correction_responses',
                function (Blueprint $table): void {
                    $table->foreign(
                        'document_version_id',
                        'corr_responses_document_version_fk',
                    )->references('id')
                        ->on('document_versions')
                        ->restrictOnDelete();
                },
            );

            Schema::table(
                'document_versions',
                function (Blueprint $table): void {
                    $table->foreign(
                        'replaces_document_version_id',
                        'document_versions_replaces_fk',
                    )->references('id')
                        ->on('document_versions')
                        ->restrictOnDelete();
                },
            );
        }
        // Os índices explícitos são criados depois das foreign keys.
        // Isto impede o MySQL/MariaDB de os reutilizar ou substituir como
        // índices internos de suporte durante a criação das constraints.
        Schema::table(
            'correction_request_items',
            function (Blueprint $table): void {
                $table->index(
                    'source_document_submission_id',
                    'corr_items_source_document_idx',
                );
            },
        );

        Schema::table(
            'correction_responses',
            function (Blueprint $table): void {
                $table->index(
                    ['correction_request_id', 'prepared_at'],
                    'corr_responses_request_prepared_idx',
                );
            },
        );

        Schema::table(
            'document_versions',
            function (Blueprint $table): void {
                $table->index(
                    'replaces_document_version_id',
                    'document_versions_replaces_idx',
                );
            },
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table(
                'document_versions',
                function (Blueprint $table): void {
                    $table->dropForeign(
                        'document_versions_replaces_fk',
                    );
                },
            );

            Schema::table(
                'correction_responses',
                function (Blueprint $table): void {
                    $table->dropForeign(
                        'corr_responses_document_version_fk',
                    );
                },
            );

            Schema::table(
                'correction_request_items',
                function (Blueprint $table): void {
                    $table->dropForeign(
                        'corr_items_source_document_fk',
                    );
                },
            );

            // MySQL pode reutilizar o índice composto criado por esta
            // migration para suportar a foreign key preexistente. A FK é
            // removida temporariamente para permitir eliminar esse índice
            // e é reposta depois com o mesmo nome e semântica originais.
            Schema::table(
                'correction_responses',
                function (Blueprint $table): void {
                    $table->dropForeign(
                        'corr_responses_request_fk',
                    );
                },
            );
        }

        Schema::table(
            'document_versions',
            function (Blueprint $table): void {
                $table->dropIndex('document_versions_replaces_idx');
                $table->dropColumn(
                    'replaces_document_version_id',
                );
            },
        );

        Schema::table(
            'correction_responses',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'corr_responses_request_item_user_uq',
                );
                $table->dropIndex(
                    'corr_responses_request_prepared_idx',
                );
                $table->dropColumn([
                    'response_kind',
                    'document_version_id',
                    'prepared_at',
                ]);
            },
        );

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table(
                'correction_responses',
                function (Blueprint $table): void {
                    $table->foreign(
                        'correction_request_id',
                        'corr_responses_request_fk',
                    )->references('id')
                        ->on('correction_requests')
                        ->cascadeOnDelete();
                },
            );
        }

        Schema::table(
            'correction_request_items',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'corr_items_source_document_idx',
                );
                $table->dropColumn([
                    'requirement_instance',
                    'source_document_submission_id',
                ]);
            },
        );
    }
};
