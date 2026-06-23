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
}
