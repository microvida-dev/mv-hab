<?php

namespace App\Services\Analytics;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperationalStatisticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{programa: string, concurso: string, estado: string, total: int}>
     */
    public function applicationsByContest(array $filters): array
    {
        if (
            ! Schema::hasTable('applications')
            || ! Schema::hasTable('contests')
            || ! Schema::hasTable('programs')
            || ! Schema::hasColumn('applications', 'contest_id')
            || ! Schema::hasColumn('applications', 'program_id')
        ) {
            return [];
        }

        $query = DB::table('applications')
            ->join('contests', 'contests.id', '=', 'applications.contest_id')
            ->join('programs', 'programs.id', '=', 'applications.program_id');

        if (isset($filters['municipality_id'])) {
            $query->where(
                'programs.municipality_id',
                (int) $filters['municipality_id'],
            );
        }

        foreach (['program_id', 'contest_id', 'status'] as $column) {
            if (isset($filters[$column]) && Schema::hasColumn('applications', $column)) {
                $query->where('applications.'.$column, $filters[$column]);
            }
        }

        return array_values($query
            ->selectRaw('programs.name as programa, contests.title as concurso, applications.status as estado, COUNT(*) as total')
            ->groupBy('programs.name', 'contests.title', 'applications.status')
            ->orderByDesc('total')
            ->limit(12)
            ->get()
            ->map(fn (object $row): array => [
                'programa' => (string) $row->programa,
                'concurso' => (string) $row->concurso,
                'estado' => (string) $row->estado,
                'total' => (int) $row->total,
            ])
            ->all());
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{dominio: string, estado: string, total: int}>
     */
    public function operationsTable(array $filters): array
    {
        $rows = [];

        foreach ([
            'document_submissions' => ['Documentos', 'status'],
            'support_tickets' => ['Apoio', 'status'],
            'maintenance_requests' => ['Manutenção', 'status'],
            'property_inspections' => ['Vistorias', 'status'],
        ] as $table => [$domain, $statusColumn]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $statusColumn)) {
                continue;
            }

            $query = DB::table($table);

            if (isset($filters['municipality_id'])) {
                $this->applyMunicipalityFilter(
                    $query,
                    $table,
                    (int) $filters['municipality_id'],
                );
            }

            $query
                ->select($statusColumn.' as status')
                ->selectRaw('COUNT(*) as total')
                ->groupBy($statusColumn)
                ->orderByDesc('total')
                ->limit(4)
                ->get()
                ->each(function (object $row) use (&$rows, $domain): void {
                    $rows[] = [
                        'dominio' => $domain,
                        'estado' => (string) $row->status,
                        'total' => (int) $row->total,
                    ];
                });
        }

        return array_slice($rows, 0, 12);
    }

    private function applyMunicipalityFilter(
        Builder $query,
        string $table,
        int $municipalityId,
    ): void {
        match ($table) {
            'document_submissions' => $query->whereIn(
                'document_submissions.application_id',
                $this->municipalApplicationIds($municipalityId),
            ),
            'support_tickets' => $query->whereIn(
                'support_tickets.user_id',
                $this->municipalUserIds($municipalityId),
            ),
            'maintenance_requests',
            'property_inspections' => $query->whereIn(
                $table.'.housing_unit_id',
                DB::table('housing_units')
                    ->where('municipality_id', $municipalityId)
                    ->select('id'),
            ),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function municipalApplicationIds(int $municipalityId): Builder
    {
        return DB::table('applications')
            ->whereIn(
                'program_id',
                DB::table('programs')
                    ->where('municipality_id', $municipalityId)
                    ->select('id'),
            )
            ->select('id');
    }

    private function municipalUserIds(int $municipalityId): Builder
    {
        return DB::table('users')
            ->where('municipality_id', $municipalityId)
            ->select('id');
    }
}
