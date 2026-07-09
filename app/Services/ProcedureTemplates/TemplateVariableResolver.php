<?php

namespace App\Services\ProcedureTemplates;

use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\Contest;
use App\Models\ProcessConfirmation;
use App\Models\User;

class TemplateVariableResolver
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    public function forProcedureMinutePayload(array $payload, ?User $actor = null): array
    {
        $selectedApplication = data_get($payload, 'application') ?: data_get($payload, 'applications.0', []);

        return [
            'municipality_name' => $this->string(data_get($payload, 'municipal.municipality_name', 'Município de Alcanena')),
            'municipal_department' => $this->string(data_get($payload, 'municipal.department')),
            'municipal_registry_number' => $this->string(data_get($payload, 'municipal.registry_number')),
            'municipal_process_number' => $this->string(data_get($payload, 'municipal.process_number')),
            'external_reference' => $this->string(data_get($payload, 'municipal.external_reference')),
            'meeting_date' => $this->string(data_get($payload, 'meeting.date')),
            'meeting_time' => $this->string(data_get($payload, 'meeting.time')),
            'meeting_location' => $this->string(data_get($payload, 'meeting.location')),
            'meeting_subject' => $this->string(data_get($payload, 'meeting.subject')),
            'contest_title' => $this->string(data_get($payload, 'contest.title')),
            'contest_code' => $this->string(data_get($payload, 'contest.code')),
            'contest_status' => $this->string(data_get($payload, 'contest.status.label')),
            'contest_applications_total' => $this->string(data_get($payload, 'summary.applications_total', 0)),
            'contest_housing_units_total' => $this->string(data_get($payload, 'summary.housing_units_total', 0)),
            'provisional_lists_total' => $this->string(data_get($payload, 'summary.provisional_lists_total', 0)),
            'definitive_lists_total' => $this->string(data_get($payload, 'summary.definitive_lists_total', 0)),
            'hearings_total' => $this->string(data_get($payload, 'summary.hearings_total', 0)),
            'complaints_total' => $this->string(data_get($payload, 'summary.complaints_total', 0)),
            'lottery_draws_total' => $this->string(data_get($payload, 'summary.lottery_draws_total', 0)),
            'withdrawals_total' => $this->string(data_get($payload, 'summary.withdrawals_total', 0)),
            'jury_members' => $this->jurySummary(data_get($payload, 'jury', [])),
            'housing_units_summary' => $this->housingUnitsSummary(data_get($payload, 'housing_units', [])),
            'applications_summary' => $this->applicationsSummary(data_get($payload, 'applications', [])),
            'provisional_list_summary' => $this->listSummary(data_get($payload, 'provisional_lists', []), 'lista provisória'),
            'definitive_list_summary' => $this->listSummary(data_get($payload, 'definitive_lists', []), 'lista definitiva'),
            'hearing_summary' => $this->hearingsSummary(data_get($payload, 'hearings', [])),
            'complaint_summary' => $this->complaintsSummary(data_get($payload, 'complaints', [])),
            'lottery_summary' => $this->lotterySummary(data_get($payload, 'lottery_draws', [])),
            'withdrawals_summary' => $this->withdrawalsSummary(data_get($payload, 'withdrawals', [])),
            'legal_basis' => $this->string(data_get($payload, 'manual_fields.legal_basis')),
            'deliberation_text' => $this->string(data_get($payload, 'manual_fields.deliberation_text')),
            'observations' => $this->string(data_get($payload, 'manual_fields.observations')),
            'generated_at' => $this->string(data_get($payload, 'generated_at', now()->format('d/m/Y H:i'))),

            'process_number' => $this->firstProcessNumber($selectedApplication),
            'application_number' => $this->string(data_get($selectedApplication, 'application_number')),
            'candidate_name' => $this->string(data_get($selectedApplication, 'candidate_name')),
            'submitted_at' => $this->string(data_get($selectedApplication, 'submitted_at')),
            'current_status' => $this->string(data_get($selectedApplication, 'status.label')),
            'ranking_position' => $this->string(data_get($selectedApplication, 'latest_score.rank_position')),
            'total_score' => $this->string(data_get($selectedApplication, 'latest_score.total_score')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function forApplication(Application $application, ?User $actor = null): array
    {
        $application->loadMissing(['user', 'contest', 'processConfirmations', 'applicationScores']);
        $latestScore = $application->applicationScores->sortByDesc('id')->first();
        $processConfirmation = $application->processConfirmations->first();

        return [
            'process_number' => $processConfirmation instanceof ProcessConfirmation ? (string) $processConfirmation->process_number : '',
            'application_number' => (string) $application->application_number,
            'candidate_name' => $actor?->hasPermission('reports.view_sensitive') ? (string) $application->user->name : 'Candidato',
            'contest_title' => (string) $application->contest->title,
            'contest_code' => (string) $application->contest->code,
            'municipality_name' => 'Município',
            'submitted_at' => (string) $application->submitted_at?->format('d/m/Y H:i'),
            'current_status' => $application->status->label(),
            'ranking_position' => '',
            'total_score' => $latestScore instanceof ApplicationScore ? (string) $latestScore->total_score : '',
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function forContest(Contest $contest): array
    {
        return [
            'contest_title' => (string) $contest->title,
            'contest_code' => (string) $contest->code,
            'municipality_name' => 'Município',
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    private function string(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    private function jurySummary(mixed $items): string
    {
        $lines = collect(is_array($items) ? $items : [])
            ->map(fn (array $item): string => trim($this->string(data_get($item, 'role_in_jury')).' — '.$this->string(data_get($item, 'user.name'))))
            ->filter()
            ->values();

        return $lines->isEmpty() ? 'Sem membros de júri registados.' : $lines->implode('; ');
    }

    private function housingUnitsSummary(mixed $items): string
    {
        $lines = collect(is_array($items) ? $items : [])
            ->map(function (array $item): string {
                $parts = array_filter([
                    $this->string(data_get($item, 'housing_unit.code')),
                    $this->string(data_get($item, 'housing_unit.typology')),
                    $this->string(data_get($item, 'housing_unit.locality')),
                    $this->string(data_get($item, 'monthly_rent')) !== '' ? $this->string(data_get($item, 'monthly_rent')).' EUR' : null,
                    $this->string(data_get($item, 'status.label')),
                ]);

                return implode(' · ', $parts);
            })
            ->filter()
            ->values();

        return $lines->isEmpty() ? 'Sem habitações associadas ao concurso.' : $lines->implode('; ');
    }

    private function applicationsSummary(mixed $items): string
    {
        $lines = collect(is_array($items) ? $items : [])
            ->map(function (array $item): string {
                $parts = array_filter([
                    $this->string(data_get($item, 'application_number')),
                    $this->string(data_get($item, 'candidate_name')),
                    $this->string(data_get($item, 'status.label')),
                    $this->string(data_get($item, 'latest_score.total_score')) !== '' ? 'Pontuação '.$this->string(data_get($item, 'latest_score.total_score')) : null,
                ]);

                return implode(' · ', $parts);
            })
            ->filter()
            ->values();

        return $lines->isEmpty() ? 'Sem candidaturas registadas para o procedimento.' : $lines->implode('; ');
    }

    private function listSummary(mixed $items, string $fallbackType): string
    {
        $lines = collect(is_array($items) ? $items : [])
            ->map(function (array $item): string {
                $entries = data_get($item, 'entries', []);
                $entriesCount = is_array($entries) ? count($entries) : 0;

                return trim($this->string(data_get($item, 'list_number')).' · '.$this->string(data_get($item, 'status.label')).' · '.$entriesCount.' entrada(s)');
            })
            ->filter()
            ->values();

        return $lines->isEmpty() ? 'Sem '.$fallbackType.' registada.' : $lines->implode('; ');
    }

    private function hearingsSummary(mixed $items): string
    {
        $lines = collect(is_array($items) ? $items : [])
            ->map(fn (array $item): string => trim($this->string(data_get($item, 'hearing_number')).' · '.$this->string(data_get($item, 'subject')).' · '.$this->string(data_get($item, 'status.label'))))
            ->filter()
            ->values();

        return $lines->isEmpty() ? 'Sem audiências prévias registadas.' : $lines->implode('; ');
    }

    private function complaintsSummary(mixed $items): string
    {
        $lines = collect(is_array($items) ? $items : [])
            ->map(fn (array $item): string => trim($this->string(data_get($item, 'complaint_number')).' · '.$this->string(data_get($item, 'subject')).' · '.$this->string(data_get($item, 'status.label'))))
            ->filter()
            ->values();

        return $lines->isEmpty() ? 'Sem reclamações registadas.' : $lines->implode('; ');
    }

    private function lotterySummary(mixed $items): string
    {
        $lines = collect(is_array($items) ? $items : [])
            ->map(fn (array $item): string => 'Sorteio '.$this->string(data_get($item, 'id')).' · '.$this->string(data_get($item, 'status.label')).' · '.$this->string(data_get($item, 'participants_count')).' participante(s) · '.$this->string(data_get($item, 'results_count')).' resultado(s)')
            ->filter()
            ->values();

        return $lines->isEmpty() ? 'Sem sorteios registados.' : $lines->implode('; ');
    }

    private function withdrawalsSummary(mixed $items): string
    {
        $lines = collect(is_array($items) ? $items : [])
            ->map(fn (array $item): string => trim($this->string(data_get($item, 'application_number')).' · '.$this->string(data_get($item, 'candidate_name')).' · '.$this->string(data_get($item, 'status.label'))))
            ->filter()
            ->values();

        return $lines->isEmpty() ? 'Sem desistências controladas registadas.' : $lines->implode('; ');
    }

    private function firstProcessNumber(mixed $application): string
    {
        if (! is_array($application)) {
            return '';
        }

        return $this->string(data_get($application, 'process_confirmations.0.process_number'));
    }
}
