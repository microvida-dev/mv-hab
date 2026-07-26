<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'visit_availabilities',
            function (Blueprint $table): void {
                $table
                    ->foreignId('municipality_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('municipalities')
                    ->restrictOnDelete();

                $table->index(
                    ['municipality_id', 'starts_at'],
                    'visit_avail_municipality_starts_idx',
                );
            },
        );

        Schema::table(
            'visit_slots',
            function (Blueprint $table): void {
                $table
                    ->foreignId('municipality_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('municipalities')
                    ->restrictOnDelete();

                $table->index(
                    ['municipality_id', 'starts_at'],
                    'visit_slots_municipality_starts_idx',
                );
            },
        );

        Schema::table(
            'housing_visits',
            function (Blueprint $table): void {
                $table
                    ->foreignId('municipality_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('municipalities')
                    ->restrictOnDelete();

                $table->index(
                    ['municipality_id', 'scheduled_at'],
                    'housing_visits_municipality_sched_idx',
                );

                $table->index(
                    ['municipality_id', 'starts_at'],
                    'housing_visits_municipality_starts_idx',
                );
            },
        );

        $this->backfillVisitAvailabilities();
        $this->backfillVisitSlots();
        $this->backfillHousingVisits();
    }

    public function down(): void
    {
        $this->dropMunicipalScopeColumn(
            table: 'housing_visits',
            foreignKey: 'housing_visits_municipality_id_foreign',
            indexes: [
                'housing_visits_municipality_sched_idx',
                'housing_visits_municipality_starts_idx',
            ],
        );

        $this->dropMunicipalScopeColumn(
            table: 'visit_slots',
            foreignKey: 'visit_slots_municipality_id_foreign',
            indexes: [
                'visit_slots_municipality_starts_idx',
            ],
        );

        $this->dropMunicipalScopeColumn(
            table: 'visit_availabilities',
            foreignKey: 'visit_availabilities_municipality_id_foreign',
            indexes: [
                'visit_avail_municipality_starts_idx',
            ],
        );
    }

    /**
     * @param  list<string>  $indexes
     */
    private function dropMunicipalScopeColumn(
        string $table,
        string $foreignKey,
        array $indexes,
    ): void {
        if (! Schema::hasColumn($table, 'municipality_id')) {
            return;
        }

        if ($this->foreignKeyExists($table, $foreignKey)) {
            Schema::table(
                $table,
                function (Blueprint $blueprint) use (
                    $foreignKey,
                ): void {
                    $blueprint->dropForeign($foreignKey);
                },
            );
        }

        foreach ($indexes as $index) {
            if (! $this->indexExists($table, $index)) {
                continue;
            }

            Schema::table(
                $table,
                function (Blueprint $blueprint) use (
                    $index,
                ): void {
                    $blueprint->dropIndex($index);
                },
            );
        }

        Schema::table(
            $table,
            function (Blueprint $blueprint): void {
                $blueprint->dropColumn('municipality_id');
            },
        );
    }

    private function foreignKeyExists(
        string $table,
        string $foreignKey,
    ): bool {
        return DB::table(
            'information_schema.TABLE_CONSTRAINTS',
        )
            ->whereRaw(
                'CONSTRAINT_SCHEMA = DATABASE()',
            )
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(
        string $table,
        string $index,
    ): bool {
        return DB::table(
            'information_schema.STATISTICS',
        )
            ->whereRaw(
                'TABLE_SCHEMA = DATABASE()',
            )
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    private function backfillVisitAvailabilities(): void
    {
        DB::table('visit_availabilities')
            ->whereNull('municipality_id')
            ->select([
                'id',
                'contest_id',
                'housing_unit_id',
            ])
            ->orderBy('id')
            ->chunkById(
                500,
                function (Collection $rows): void {
                    $contestMunicipalities =
                        $this->contestMunicipalityMap(
                            $this->ids($rows, 'contest_id'),
                        );

                    $housingMunicipalities =
                        $this->housingMunicipalityMap(
                            $this->ids($rows, 'housing_unit_id'),
                        );

                    foreach ($rows as $row) {
                        $contestId = $row->contest_id !== null
                            ? (int) $row->contest_id
                            : null;

                        $housingUnitId =
                            $row->housing_unit_id !== null
                                ? (int) $row->housing_unit_id
                                : null;

                        $municipalityId =
                            $this->resolveMunicipalityId([
                                $this->source(
                                    $contestId,
                                    $contestMunicipalities,
                                ),
                                $this->source(
                                    $housingUnitId,
                                    $housingMunicipalities,
                                ),
                            ]);

                        if ($municipalityId === null) {
                            continue;
                        }

                        DB::table('visit_availabilities')
                            ->where('id', (int) $row->id)
                            ->whereNull('municipality_id')
                            ->update([
                                'municipality_id' => $municipalityId,
                            ]);
                    }
                },
                'id',
            );
    }

    private function backfillVisitSlots(): void
    {
        DB::table('visit_slots')
            ->whereNull('municipality_id')
            ->select([
                'id',
                'visit_availability_id',
                'contest_id',
                'housing_unit_id',
            ])
            ->orderBy('id')
            ->chunkById(
                500,
                function (Collection $rows): void {
                    $availabilityMunicipalities =
                        $this->canonicalMunicipalityMap(
                            'visit_availabilities',
                            $this->ids(
                                $rows,
                                'visit_availability_id',
                            ),
                        );

                    $contestMunicipalities =
                        $this->contestMunicipalityMap(
                            $this->ids($rows, 'contest_id'),
                        );

                    $housingMunicipalities =
                        $this->housingMunicipalityMap(
                            $this->ids($rows, 'housing_unit_id'),
                        );

                    foreach ($rows as $row) {
                        $availabilityId =
                            $row->visit_availability_id !== null
                                ? (int) $row->visit_availability_id
                                : null;

                        $contestId = $row->contest_id !== null
                            ? (int) $row->contest_id
                            : null;

                        $housingUnitId =
                            $row->housing_unit_id !== null
                                ? (int) $row->housing_unit_id
                                : null;

                        $municipalityId =
                            $this->resolveMunicipalityId([
                                $this->source(
                                    $availabilityId,
                                    $availabilityMunicipalities,
                                ),
                                $this->source(
                                    $contestId,
                                    $contestMunicipalities,
                                ),
                                $this->source(
                                    $housingUnitId,
                                    $housingMunicipalities,
                                ),
                            ]);

                        if ($municipalityId === null) {
                            continue;
                        }

                        DB::table('visit_slots')
                            ->where('id', (int) $row->id)
                            ->whereNull('municipality_id')
                            ->update([
                                'municipality_id' => $municipalityId,
                            ]);
                    }
                },
                'id',
            );
    }

    private function backfillHousingVisits(): void
    {
        DB::table('housing_visits')
            ->whereNull('municipality_id')
            ->select([
                'id',
                'visit_slot_id',
                'application_id',
                'contest_id',
                'housing_unit_id',
            ])
            ->orderBy('id')
            ->chunkById(
                500,
                function (Collection $rows): void {
                    $slotMunicipalities =
                        $this->canonicalMunicipalityMap(
                            'visit_slots',
                            $this->ids($rows, 'visit_slot_id'),
                        );

                    $applicationMunicipalities =
                        $this->applicationMunicipalityMap(
                            $this->ids($rows, 'application_id'),
                        );

                    $contestMunicipalities =
                        $this->contestMunicipalityMap(
                            $this->ids($rows, 'contest_id'),
                        );

                    $housingMunicipalities =
                        $this->housingMunicipalityMap(
                            $this->ids($rows, 'housing_unit_id'),
                        );

                    foreach ($rows as $row) {
                        $slotId = $row->visit_slot_id !== null
                            ? (int) $row->visit_slot_id
                            : null;

                        $applicationId =
                            $row->application_id !== null
                                ? (int) $row->application_id
                                : null;

                        $contestId = $row->contest_id !== null
                            ? (int) $row->contest_id
                            : null;

                        $housingUnitId =
                            $row->housing_unit_id !== null
                                ? (int) $row->housing_unit_id
                                : null;

                        $municipalityId =
                            $this->resolveMunicipalityId([
                                $this->source(
                                    $slotId,
                                    $slotMunicipalities,
                                ),
                                $this->source(
                                    $applicationId,
                                    $applicationMunicipalities,
                                ),
                                $this->source(
                                    $contestId,
                                    $contestMunicipalities,
                                ),
                                $this->source(
                                    $housingUnitId,
                                    $housingMunicipalities,
                                ),
                            ]);

                        if ($municipalityId === null) {
                            continue;
                        }

                        DB::table('housing_visits')
                            ->where('id', (int) $row->id)
                            ->whereNull('municipality_id')
                            ->update([
                                'municipality_id' => $municipalityId,
                            ]);
                    }
                },
                'id',
            );
    }

    /**
     * Uma relação ausente é ignorada.
     *
     * Uma relação presente sem Município resolvido torna o registo
     * inválido. Todas as relações presentes têm de indicar exatamente
     * o mesmo Município.
     *
     * @param  list<array{0: int|null, 1: int|null}>  $sources
     */
    private function resolveMunicipalityId(
        array $sources,
    ): ?int {
        $municipalityIds = [];

        foreach ($sources as [$foreignId, $municipalityId]) {
            if ($foreignId === null) {
                continue;
            }

            if ($municipalityId === null) {
                return null;
            }

            $municipalityIds[] = $municipalityId;
        }

        $municipalityIds = array_values(
            array_unique($municipalityIds),
        );

        return count($municipalityIds) === 1
            ? $municipalityIds[0]
            : null;
    }

    /**
     * @param  array<int, int|null>  $municipalityMap
     * @return array{0: int|null, 1: int|null}
     */
    private function source(
        ?int $foreignId,
        array $municipalityMap,
    ): array {
        if ($foreignId === null) {
            return [null, null];
        }

        if (
            ! array_key_exists(
                $foreignId,
                $municipalityMap,
            )
            || $municipalityMap[$foreignId] === null
        ) {
            return [$foreignId, null];
        }

        return [
            $foreignId,
            (int) $municipalityMap[$foreignId],
        ];
    }

    /**
     * @param  Collection<int, stdClass>  $rows
     * @return list<int>
     */
    private function ids(
        Collection $rows,
        string $column,
    ): array {
        $ids = $rows
            ->pluck($column)
            ->filter(
                static fn (mixed $id): bool => $id !== null,
            )
            ->map(
                static fn (mixed $id): int => (int) $id,
            )
            ->unique()
            ->values()
            ->all();

        return array_values($ids);
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, int|null>
     */
    private function contestMunicipalityMap(
        array $ids,
    ): array {
        if ($ids === []) {
            return [];
        }

        $map = [];

        $rows = DB::table('contests')
            ->join(
                'programs',
                'programs.id',
                '=',
                'contests.program_id',
            )
            ->whereIn('contests.id', $ids)
            ->get([
                'contests.id',
                'programs.municipality_id',
            ]);

        foreach ($rows as $row) {
            $map[(int) $row->id] =
                $row->municipality_id !== null
                    ? (int) $row->municipality_id
                    : null;
        }

        return $map;
    }

    /**
     * A candidatura só é considerada municipalmente válida quando
     * o seu programa e o programa do respetivo concurso pertencem
     * ao mesmo Município.
     *
     * @param  list<int>  $ids
     * @return array<int, int|null>
     */
    private function applicationMunicipalityMap(
        array $ids,
    ): array {
        if ($ids === []) {
            return [];
        }

        $map = [];

        $rows = DB::table('applications')
            ->join(
                'programs as application_programs',
                'application_programs.id',
                '=',
                'applications.program_id',
            )
            ->join(
                'contests',
                'contests.id',
                '=',
                'applications.contest_id',
            )
            ->join(
                'programs as contest_programs',
                'contest_programs.id',
                '=',
                'contests.program_id',
            )
            ->whereIn('applications.id', $ids)
            ->get([
                'applications.id',
                'application_programs.municipality_id'
                    .' as application_municipality_id',
                'contest_programs.municipality_id'
                    .' as contest_municipality_id',
            ]);

        foreach ($rows as $row) {
            $applicationMunicipalityId =
                (int) $row->application_municipality_id;

            $contestMunicipalityId =
                (int) $row->contest_municipality_id;

            $map[(int) $row->id] =
                $applicationMunicipalityId
                    === $contestMunicipalityId
                        ? $applicationMunicipalityId
                        : null;
        }

        return $map;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, int|null>
     */
    private function housingMunicipalityMap(
        array $ids,
    ): array {
        if ($ids === []) {
            return [];
        }

        $map = [];

        $rows = DB::table('housing_units')
            ->whereIn('id', $ids)
            ->get([
                'id',
                'municipality_id',
            ]);

        foreach ($rows as $row) {
            $map[(int) $row->id] =
                $row->municipality_id !== null
                    ? (int) $row->municipality_id
                    : null;
        }

        return $map;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, int|null>
     */
    private function canonicalMunicipalityMap(
        string $table,
        array $ids,
    ): array {
        if ($ids === []) {
            return [];
        }

        $map = [];

        $rows = DB::table($table)
            ->whereIn('id', $ids)
            ->get([
                'id',
                'municipality_id',
            ]);

        foreach ($rows as $row) {
            $map[(int) $row->id] =
                $row->municipality_id !== null
                    ? (int) $row->municipality_id
                    : null;
        }

        return $map;
    }
};
