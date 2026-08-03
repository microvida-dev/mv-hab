<?php

use App\Enums\ApplicationPreferenceSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('preference_source', 40)
                ->default(ApplicationPreferenceSource::Uninitialized->value)
                ->after('regulatory_snapshot_id');
            $table->timestamp('official_preferences_initialized_at')
                ->nullable()
                ->after('preference_source');
            $table->timestamp('legacy_preferences_reconciled_at')
                ->nullable()
                ->after('official_preferences_initialized_at');
            $table->index(
                'preference_source',
                'applications_preference_source_idx',
            );
        });

        DB::table('applications')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(250, function (Collection $applications): void {
                $applicationIds = $applications
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all();

                $official = DB::table('housing_preferences')
                    ->selectRaw(
                        'application_id, COUNT(*) AS total, '.
                        'SUM(CASE WHEN legacy_application_preference_id IS NOT NULL THEN 1 ELSE 0 END) AS linked_total, '.
                        'MIN(created_at) AS first_created_at, MAX(updated_at) AS last_updated_at',
                    )
                    ->whereIn('application_id', $applicationIds)
                    ->groupBy('application_id')
                    ->get()
                    ->keyBy('application_id');
                $legacy = DB::table('application_preferences')
                    ->selectRaw('application_id, COUNT(*) AS total')
                    ->whereIn('application_id', $applicationIds)
                    ->groupBy('application_id')
                    ->get()
                    ->keyBy('application_id');

                foreach ($applicationIds as $applicationId) {
                    $officialRow = $official->get($applicationId);
                    $legacyRow = $legacy->get($applicationId);
                    $officialTotal = $officialRow instanceof stdClass
                        ? (int) $officialRow->total
                        : 0;
                    $linkedTotal = $officialRow instanceof stdClass
                        ? (int) $officialRow->linked_total
                        : 0;
                    $legacyTotal = (int) ($legacyRow->total ?? 0);
                    $firstCreatedAt = $officialRow instanceof stdClass
                        ? $officialRow->first_created_at
                        : null;
                    $lastUpdatedAt = $officialRow instanceof stdClass
                        ? $officialRow->last_updated_at
                        : null;
                    $source = match (true) {
                        $officialTotal === 0 && $legacyTotal === 0 => ApplicationPreferenceSource::Uninitialized,
                        $officialTotal === 0 => ApplicationPreferenceSource::Legacy,
                        $legacyTotal === 0 => ApplicationPreferenceSource::Official,
                        $linkedTotal === $officialTotal
                            && $linkedTotal === $legacyTotal => ApplicationPreferenceSource::Reconciled,
                        default => ApplicationPreferenceSource::RequiresManualReview,
                    };

                    DB::table('applications')
                        ->where('id', $applicationId)
                        ->update([
                            'preference_source' => $source->value,
                            'official_preferences_initialized_at' => $officialTotal > 0
                                ? $firstCreatedAt
                                : null,
                            'legacy_preferences_reconciled_at' => $source === ApplicationPreferenceSource::Reconciled
                                ? $lastUpdatedAt
                                : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex('applications_preference_source_idx');
            $table->dropColumn([
                'preference_source',
                'official_preferences_initialized_at',
                'legacy_preferences_reconciled_at',
            ]);
        });
    }
};
