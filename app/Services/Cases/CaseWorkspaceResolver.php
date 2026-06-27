<?php

namespace App\Services\Cases;

use App\Models\Application;
use Illuminate\Database\Eloquent\Model;

class CaseWorkspaceResolver
{
    /**
     * @return array<string, array{label: string, model: class-string<Model>|null, implemented: bool}>
     */
    public function supportedTypes(): array
    {
        return [
            'application' => ['label' => 'Candidatura', 'model' => Application::class, 'implemented' => true],
            'contract' => ['label' => 'Contrato', 'model' => null, 'implemented' => false],
            'maintenance_request' => ['label' => 'Manutenção', 'model' => null, 'implemented' => false],
            'inspection' => ['label' => 'Vistoria', 'model' => null, 'implemented' => false],
            'complaint' => ['label' => 'Reclamação', 'model' => null, 'implemented' => false],
            'support_ticket' => ['label' => 'Ticket', 'model' => null, 'implemented' => false],
            'contest' => ['label' => 'Concurso', 'model' => null, 'implemented' => false],
        ];
    }

    public function typeFor(Model $case): string
    {
        return $case instanceof Application ? 'application' : 'unknown';
    }
}
