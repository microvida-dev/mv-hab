<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = [
        'consent_purposes',
        'retention_policies',
        'retention_executions',
        'data_subject_requests',
        'data_export_packages',
        'anonymization_requests',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('municipality_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        $this->backfillFromUsers('consent_purposes', ['created_by']);
        $this->backfillFromUsers('retention_policies', ['created_by']);
        $this->backfillFromUsers('data_subject_requests', ['user_id', 'assigned_to', 'created_by']);
        $this->backfillFromParent(
            'retention_executions',
            'retention_policy_id',
            'retention_policies',
        );
        $this->backfillFromParent(
            'data_export_packages',
            'data_subject_request_id',
            'data_subject_requests',
        );
        $this->backfillFromUsers('data_export_packages', ['user_id', 'generated_by']);
        $this->backfillFromParent(
            'anonymization_requests',
            'data_subject_request_id',
            'data_subject_requests',
        );
        $this->backfillFromUsers('anonymization_requests', ['user_id', 'approved_by', 'executed_by']);
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('municipality_id');
            });
        }
    }

    /**
     * @param  list<string>  $userColumns
     */
    private function backfillFromUsers(string $tableName, array $userColumns): void
    {
        DB::table($tableName)
            ->whereNull('municipality_id')
            ->orderBy('id')
            ->chunkById(200, function (Collection $rows) use ($tableName, $userColumns): void {
                $userIds = $rows
                    ->flatMap(fn (object $row): array => array_map(
                        fn (string $column): mixed => $row->{$column},
                        $userColumns,
                    ))
                    ->filter()
                    ->map(fn (mixed $id): int => (int) $id)
                    ->unique()
                    ->values();

                $municipalities = DB::table('users')
                    ->whereIn('id', $userIds)
                    ->pluck('municipality_id', 'id');

                foreach ($rows as $row) {
                    foreach ($userColumns as $column) {
                        $userId = $row->{$column};
                        $municipalityId = $userId === null
                            ? null
                            : $municipalities->get((int) $userId);

                        if ($municipalityId !== null) {
                            DB::table($tableName)
                                ->where('id', $row->id)
                                ->update(['municipality_id' => $municipalityId]);

                            break;
                        }
                    }
                }
            });
    }

    private function backfillFromParent(
        string $tableName,
        string $foreignKey,
        string $parentTable,
    ): void {
        DB::table($tableName)
            ->whereNull('municipality_id')
            ->whereNotNull($foreignKey)
            ->orderBy('id')
            ->chunkById(200, function (Collection $rows) use ($tableName, $foreignKey, $parentTable): void {
                $parentIds = $rows
                    ->pluck($foreignKey)
                    ->filter()
                    ->map(fn (mixed $id): int => (int) $id)
                    ->unique()
                    ->values();

                $municipalities = DB::table($parentTable)
                    ->whereIn('id', $parentIds)
                    ->pluck('municipality_id', 'id');

                foreach ($rows as $row) {
                    $parentId = $row->{$foreignKey};
                    $municipalityId = $parentId === null
                        ? null
                        : $municipalities->get((int) $parentId);

                    if ($municipalityId !== null) {
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update(['municipality_id' => $municipalityId]);
                    }
                }
            });
    }
};
