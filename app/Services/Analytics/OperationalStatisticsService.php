<?php

namespace App\Services\Analytics;

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

            DB::table($table)
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
}
