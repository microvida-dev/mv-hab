<?php

namespace App\Services\Analytics;

use App\Data\Analytics\FunnelStepData;
use App\Models\DefinitiveList;
use App\Models\ProvisionalList;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FunnelAnalysisService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{label: string, value: int, description: string, status: string}>
     */
    public function municipalFlow(array $filters): array
    {
        $steps = [
            new FunnelStepData('Simulação', $this->distinctCount('simulation_recommended_contests', 'simulation_id', $filters), 'Simulações com recomendação registada.', 'info'),
            new FunnelStepData('Registo', $this->countRows('adhesion_registrations', $filters), 'Registos de adesão criados.', 'info'),
            new FunnelStepData('Candidatura', $this->countRows('applications', $filters), 'Candidaturas criadas no período.', 'civic'),
            new FunnelStepData('Documentos', $this->distinctCount('document_submissions', 'application_id', $filters), 'Processos com documentos submetidos.', 'warning'),
            new FunnelStepData('Elegibilidade', $this->distinctCount('eligibility_checks', 'application_id', $filters), 'Candidaturas com verificação de elegibilidade.', 'pending'),
            new FunnelStepData('Pontuação', $this->distinctCount('scoring_runs', 'application_id', $filters), 'Candidaturas com registo de pontuação quando disponível.', 'pending'),
            new FunnelStepData('Lista provisória', $this->listCount('provisional', $filters), 'Publicações provisórias agregadas.', 'neutral'),
            new FunnelStepData('Audiência', $this->countRows('hearings', $filters), 'Audiências e pronúncias agregadas.', 'neutral'),
            new FunnelStepData('Lista definitiva', $this->listCount('definitive', $filters), 'Publicações definitivas agregadas.', 'neutral'),
            new FunnelStepData('Atribuição', $this->countRows('allocation_offers', $filters), 'Ofertas ou atribuições registadas.', 'success'),
            new FunnelStepData('Contrato', $this->countRows('contracts', $filters), 'Contratos criados.', 'success'),
        ];

        return array_map(
            fn (FunnelStepData $step): array => $step->toArray(),
            $steps,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function countRows(string $table, array $filters): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        $this->applyFilters($query, $table, $filters);

        return (int) $query->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function distinctCount(string $table, string $column, array $filters): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        $query = DB::table($table);
        $this->applyFilters($query, $table, $filters);

        return (int) $query->whereNotNull($column)->distinct()->count($column);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function listCount(string $type, array $filters): int
    {
        if (! Schema::hasTable('list_publications')) {
            return 0;
        }

        $query = DB::table('list_publications');

        if (Schema::hasColumn('list_publications', 'publication_type')) {
            $query->where('publication_type', $type);
        }

        $this->applyFilters($query, 'list_publications', $filters);

        return (int) $query->count();
    }

    /**
     * @param  Builder  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, string $table, array $filters): void
    {
        if (isset($filters['municipality_id'])) {
            $this->applyMunicipalityFilter(
                $query,
                $table,
                (int) $filters['municipality_id'],
            );
        }

        foreach (['program_id', 'contest_id', 'status'] as $column) {
            if (isset($filters[$column]) && Schema::hasColumn($table, $column)) {
                $query->where($table.'.'.$column, $filters[$column]);
            }
        }

        $dateColumn = Schema::hasColumn($table, 'created_at') ? 'created_at' : null;
        if ($dateColumn === null) {
            return;
        }

        if (isset($filters['date_from'])) {
            $query->whereDate($table.'.'.$dateColumn, '>=', (string) $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate($table.'.'.$dateColumn, '<=', (string) $filters['date_to']);
        }
    }

    private function applyMunicipalityFilter(
        Builder $query,
        string $table,
        int $municipalityId,
    ): void {
        if (Schema::hasColumn($table, 'municipality_id')) {
            $query->where($table.'.municipality_id', $municipalityId);

            return;
        }

        match ($table) {
            'simulation_recommended_contests',
            'applications',
            'eligibility_checks',
            'scoring_runs',
            'contracts' => $query->whereIn(
                $table.'.program_id',
                $this->municipalProgramIds($municipalityId),
            ),
            'adhesion_registrations' => $query->whereIn(
                $table.'.user_id',
                $this->municipalUserIds($municipalityId),
            ),
            'document_submissions',
            'hearings',
            'allocation_offers' => $query->whereIn(
                $table.'.application_id',
                $this->municipalApplicationIds($municipalityId),
            ),
            'list_publications' => $this->applyListPublicationMunicipality(
                $query,
                $municipalityId,
            ),
            default => $query->whereRaw('1 = 0'),
        };
    }

    private function municipalProgramIds(int $municipalityId): Builder
    {
        return DB::table('programs')
            ->where('municipality_id', $municipalityId)
            ->select('id');
    }

    private function municipalApplicationIds(int $municipalityId): Builder
    {
        return DB::table('applications')
            ->whereIn(
                'program_id',
                $this->municipalProgramIds($municipalityId),
            )
            ->select('id');
    }

    private function municipalUserIds(int $municipalityId): Builder
    {
        return DB::table('users')
            ->where('municipality_id', $municipalityId)
            ->select('id');
    }

    private function applyListPublicationMunicipality(
        Builder $query,
        int $municipalityId,
    ): void {
        $query->where(function (Builder $publications) use (
            $municipalityId,
        ): void {
            $publications
                ->where(function (Builder $provisional) use (
                    $municipalityId,
                ): void {
                    $provisional
                        ->where(
                            'list_publications.publishable_type',
                            (new ProvisionalList)->getMorphClass(),
                        )
                        ->whereIn(
                            'list_publications.publishable_id',
                            DB::table('provisional_lists')
                                ->whereIn(
                                    'program_id',
                                    $this->municipalProgramIds(
                                        $municipalityId,
                                    ),
                                )
                                ->select('id'),
                        );
                })
                ->orWhere(function (Builder $definitive) use (
                    $municipalityId,
                ): void {
                    $definitive
                        ->where(
                            'list_publications.publishable_type',
                            (new DefinitiveList)->getMorphClass(),
                        )
                        ->whereIn(
                            'list_publications.publishable_id',
                            DB::table('definitive_lists')
                                ->whereIn(
                                    'program_id',
                                    $this->municipalProgramIds(
                                        $municipalityId,
                                    ),
                                )
                                ->select('id'),
                        );
                });
        });
    }
}
