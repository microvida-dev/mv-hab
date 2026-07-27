<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsNamedForeignKeyDrops = Schema::getConnection()
            ->getDriverName() !== 'sqlite';

        Schema::table('allocation_rule_sets', function (Blueprint $table): void {
            $table->unsignedTinyInteger('minimum_preferences')->default(0)->after('allow_preferences');
            $table->unsignedTinyInteger('maximum_preferences')->default(3)->after('minimum_preferences');
            $table->boolean('preferences_required_before_submission')->default(false)->after('maximum_preferences');
            $table->boolean('allow_unselected_unit_fallback')->default(false)->after('preferences_required_before_submission');
            $table->timestamp('preference_selection_starts_at')->nullable()->after('allow_unselected_unit_fallback');
            $table->timestamp('preference_selection_ends_at')->nullable()->after('preference_selection_starts_at');
        });

        Schema::table('housing_preferences', function (Blueprint $table): void {
            $table->string('compatibility_status', 80)->nullable()->after('notes');
            $table->json('compatibility_snapshot')->nullable()->after('compatibility_status');
            $table->unsignedBigInteger('regulatory_snapshot_id')->nullable()->after('compatibility_snapshot');
            $table->timestamp('evaluated_at')->nullable()->after('regulatory_snapshot_id');
            $table->timestamp('invalidated_at')->nullable()->after('evaluated_at');
            $table->string('invalidation_reason', 500)->nullable()->after('invalidated_at');
            $table->timestamp('locked_at')->nullable()->after('submitted_at');
            $table->unsignedBigInteger('legacy_application_preference_id')->nullable()->after('locked_at');

            $table->index(
                ['application_id', 'compatibility_status'],
                'hp_application_compatibility_idx',
            );
            $table->index('regulatory_snapshot_id', 'hp_reg_snapshot_idx');
            $table->unique(
                'legacy_application_preference_id',
                'hp_legacy_preference_unique',
            );
        });

        if ($supportsNamedForeignKeyDrops) {
            Schema::table('housing_preferences', function (Blueprint $table): void {
                $table->foreign('regulatory_snapshot_id', 'hp_regulatory_snapshot_fk')
                    ->references('id')
                    ->on('regulatory_snapshots')
                    ->restrictOnDelete();
                $table->foreign('legacy_application_preference_id', 'hp_legacy_preference_fk')
                    ->references('id')
                    ->on('application_preferences')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('housing_preferences', function (Blueprint $table): void {
                $table->dropForeign('hp_regulatory_snapshot_fk');
                $table->dropForeign('hp_legacy_preference_fk');
            });
        }

        Schema::table('housing_preferences', function (Blueprint $table): void {
            $table->dropIndex('hp_application_compatibility_idx');
            $table->dropIndex('hp_reg_snapshot_idx');
            $table->dropUnique('hp_legacy_preference_unique');
            $table->dropColumn([
                'compatibility_status',
                'compatibility_snapshot',
                'regulatory_snapshot_id',
                'evaluated_at',
                'invalidated_at',
                'invalidation_reason',
                'locked_at',
                'legacy_application_preference_id',
            ]);
        });

        Schema::table('allocation_rule_sets', function (Blueprint $table): void {
            $table->dropColumn([
                'minimum_preferences',
                'maximum_preferences',
                'preferences_required_before_submission',
                'allow_unselected_unit_fallback',
                'preference_selection_starts_at',
                'preference_selection_ends_at',
            ]);
        });
    }
};
