<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'document_dossier_items',
            function (Blueprint $table): void {
                $table
                    ->string('target_type', 80)
                    ->nullable()
                    ->after('document_type_id');

                $table
                    ->unsignedBigInteger('target_id')
                    ->nullable()
                    ->after('target_type');

                $table
                    ->string('target_label')
                    ->nullable()
                    ->after('target_id');

                $table
                    ->unsignedSmallInteger('requirement_instance')
                    ->default(1)
                    ->after('target_label');

                $table
                    ->unsignedSmallInteger('required_submissions')
                    ->default(1)
                    ->after('requirement_instance');

                $table
                    ->date('reference_period')
                    ->nullable()
                    ->after('required_submissions');

                $table->index(
                    ['target_type', 'target_id'],
                    'dossier_items_target_idx',
                );

                $table->index(
                    'reference_period',
                    'dossier_items_reference_period_idx',
                );
            },
        );
    }

    public function down(): void
    {
        /*
         * Uma versão anterior desta migration criou um índice composto
         * iniciado por document_dossier_id. Em MySQL/MariaDB esse índice
         * pode passar a suportar a foreign key existente.
         *
         * Garantimos primeiro um índice dedicado antes de remover
         * eventualmente o índice composto legado.
         */
        $hasDedicatedDossierIndex = collect(
            Schema::getIndexes('document_dossier_items'),
        )->contains(
            static fn (array $index): bool => ($index['columns'] ?? []) === [
                'document_dossier_id',
            ],
        );

        if (! $hasDedicatedDossierIndex) {
            Schema::table(
                'document_dossier_items',
                function (Blueprint $table): void {
                    $table->index(
                        'document_dossier_id',
                        'document_dossier_items_document_dossier_id_index',
                    );
                },
            );
        }

        if (Schema::hasIndex(
            'document_dossier_items',
            'dossier_items_requirement_instance_idx',
        )) {
            Schema::table(
                'document_dossier_items',
                function (Blueprint $table): void {
                    $table->dropIndex(
                        'dossier_items_requirement_instance_idx',
                    );
                },
            );
        }

        Schema::table(
            'document_dossier_items',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'dossier_items_target_idx',
                );

                $table->dropIndex(
                    'dossier_items_reference_period_idx',
                );

                $table->dropColumn([
                    'target_type',
                    'target_id',
                    'target_label',
                    'requirement_instance',
                    'required_submissions',
                    'reference_period',
                ]);
            },
        );
    }
};
