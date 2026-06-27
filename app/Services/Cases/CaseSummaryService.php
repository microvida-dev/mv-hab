<?php

namespace App\Services\Cases;

use App\Models\Application;
use App\Models\User;

class CaseSummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function forApplication(User $user, Application $application): array
    {
        $application->loadMissing(['contest', 'program', 'user', 'administrativeProcess']);

        return [
            'type' => 'application',
            'title' => 'Candidatura',
            'reference' => $application->application_number ?? $application->public_id,
            'status' => $application->status->label(),
            'status_value' => $application->status->value,
            'description' => $application->contest->title,
            'program' => $application->program->name,
            'case_owner' => 'Candidato registado',
            'responsible' => 'Equipa municipal competente',
            'team' => 'Gabinete Técnico',
            'priority' => $this->priority($application),
            'due_at' => $this->dueAt($application),
            'sla' => $this->slaLabel($application),
            'created_at' => $application->created_at,
            'submitted_at' => $application->submitted_at,
            'primary_route' => 'backoffice.cases.applications.show',
            'primary_route_parameters' => [$application],
        ];
    }

    private function priority(Application $application): string
    {
        return $application->correctionRequests()->where('response_deadline_at', '<', now())->exists()
            ? 'Alta'
            : 'Normal';
    }

    private function dueAt(Application $application): mixed
    {
        return $application->correctionRequests()
            ->whereNotNull('response_deadline_at')
            ->orderBy('response_deadline_at')
            ->value('response_deadline_at');
    }

    private function slaLabel(Application $application): string
    {
        return $this->dueAt($application) === null ? 'Sem prazo operacional ativo' : 'Prazo de aperfeiçoamento ativo';
    }
}
