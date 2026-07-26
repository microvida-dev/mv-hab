<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('required_documents', function (Blueprint $table): void {
            $table->unsignedTinyInteger('required_submissions')
                ->default(1)
                ->after('is_active');

            $table->string('reference_period_unit', 30)
                ->nullable()
                ->after('required_submissions');

            $table->boolean('requires_distinct_reference_periods')
                ->default(false)
                ->after('reference_period_unit');

            $table->unsignedTinyInteger('reference_period_recency')
                ->nullable()
                ->after('requires_distinct_reference_periods');
        });

        Schema::table('document_submissions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('requirement_instance')
                ->default(1)
                ->after('required_document_id');

            $table->date('reference_period')
                ->nullable()
                ->after('requirement_instance');

            /*
             * As colunas próprias da funcionalidade ficam primeiro para impedir
             * que o MySQL reutilize estes índices como suporte das foreign keys.
             */
            $table->index(
                ['requirement_instance', 'required_document_id', 'income_record_id'],
                'doc_sub_required_income_instance_idx',
            );

            $table->index(
                ['reference_period', 'required_document_id', 'income_record_id'],
                'doc_sub_required_income_period_idx',
            );
        });
    }

    public function down(): void
    {
        /*
         * O MySQL pode escolher um índice composto para suportar uma foreign
         * key. Removemos temporariamente as duas constraints para tornar o
         * rollback determinístico, incluindo após uma tentativa parcial.
         */
        Schema::table('document_submissions', function (Blueprint $table): void {
            $table->dropForeign(
                'document_submissions_required_document_id_foreign',
            );

            $table->dropForeign(
                'document_submissions_income_record_id_foreign',
            );
        });

        foreach ([
            'doc_sub_required_income_instance_idx',
            'doc_sub_required_income_period_idx',
        ] as $index) {
            if (Schema::hasIndex('document_submissions', $index)) {
                Schema::table(
                    'document_submissions',
                    fn (Blueprint $table) => $table->dropIndex($index),
                );
            }
        }

        Schema::table('document_submissions', function (Blueprint $table): void {
            $table->dropColumn([
                'requirement_instance',
                'reference_period',
            ]);
        });

        Schema::table('document_submissions', function (Blueprint $table): void {
            $table->foreign(
                'required_document_id',
                'document_submissions_required_document_id_foreign',
            )
                ->references('id')
                ->on('required_documents')
                ->nullOnDelete();

            $table->foreign(
                'income_record_id',
                'document_submissions_income_record_id_foreign',
            )
                ->references('id')
                ->on('income_records')
                ->nullOnDelete();
        });

        Schema::table('required_documents', function (Blueprint $table): void {
            $table->dropColumn([
                'required_submissions',
                'reference_period_unit',
                'requires_distinct_reference_periods',
                'reference_period_recency',
            ]);
        });
    }
};
