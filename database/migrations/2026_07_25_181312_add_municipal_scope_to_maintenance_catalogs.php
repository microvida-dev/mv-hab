<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const SYSTEM_MAINTENANCE_CATEGORY_CODES = [
        'plumbing',
        'electricity',
        'structure',
        'equipment',
    ];

    private const SYSTEM_INSPECTION_TEMPLATE_CODE = 'housing-standard-demo';

    public function up(): void
    {
        Schema::table('maintenance_categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('municipality_id')
                ->nullable()
                ->after('parent_id');

            $table->boolean('is_system')
                ->default(false)
                ->after('municipality_id');

            $table->index(
                ['municipality_id', 'is_system'],
                'maint_cat_municipality_system_idx',
            );

            $table->foreign(
                'municipality_id',
                'maint_cat_municipality_fk',
            )
                ->references('id')
                ->on('municipalities')
                ->restrictOnDelete();
        });

        Schema::table('maintenance_suppliers', function (Blueprint $table): void {
            $table->unsignedBigInteger('municipality_id')
                ->nullable()
                ->after('id');

            $table->index(
                'municipality_id',
                'maint_supplier_municipality_idx',
            );

            $table->foreign(
                'municipality_id',
                'maint_supplier_municipality_fk',
            )
                ->references('id')
                ->on('municipalities')
                ->restrictOnDelete();
        });

        Schema::table('inspection_checklist_templates', function (Blueprint $table): void {
            $table->unsignedBigInteger('municipality_id')
                ->nullable()
                ->after('id');

            $table->boolean('is_system')
                ->default(false)
                ->after('municipality_id');

            $table->index(
                ['municipality_id', 'is_system'],
                'insp_tpl_municipality_system_idx',
            );

            $table->foreign(
                'municipality_id',
                'insp_tpl_municipality_fk',
            )
                ->references('id')
                ->on('municipalities')
                ->restrictOnDelete();
        });

        DB::table('maintenance_categories')
            ->whereNull('municipality_id')
            ->whereIn(
                'code',
                self::SYSTEM_MAINTENANCE_CATEGORY_CODES,
            )
            ->update([
                'is_system' => true,
            ]);

        DB::table('inspection_checklist_templates')
            ->whereNull('municipality_id')
            ->where(
                'code',
                self::SYSTEM_INSPECTION_TEMPLATE_CODE,
            )
            ->update([
                'is_system' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('inspection_checklist_templates', function (Blueprint $table): void {
            $this->dropForeignConstraint($table, 'insp_tpl_municipality_fk', ['municipality_id']);
            $table->dropIndex('insp_tpl_municipality_system_idx');
            $table->dropColumn([
                'is_system',
                'municipality_id',
            ]);
        });

        Schema::table('maintenance_suppliers', function (Blueprint $table): void {
            $this->dropForeignConstraint($table, 'maint_supplier_municipality_fk', ['municipality_id']);
            $table->dropIndex('maint_supplier_municipality_idx');
            $table->dropColumn('municipality_id');
        });

        Schema::table('maintenance_categories', function (Blueprint $table): void {
            $this->dropForeignConstraint($table, 'maint_cat_municipality_fk', ['municipality_id']);
            $table->dropIndex('maint_cat_municipality_system_idx');
            $table->dropColumn([
                'is_system',
                'municipality_id',
            ]);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropForeignConstraint(
        Blueprint $table,
        string $constraint,
        array $columns,
    ): void {
        $table->dropForeign(
            Schema::getConnection()->getDriverName() === 'sqlite'
                ? $columns
                : $constraint,
        );
    }
};
