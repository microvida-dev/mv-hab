<?php

namespace App\Services\Cases;

use App\Enums\DocumentStatus;
use App\Models\Application;

class ProcessChecklistService
{
    /**
     * @return list<array<string, string>>
     */
    public function forApplication(Application $application): array
    {
        $documentCounts = $application->documentSubmissions()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingDocuments = collect([
            DocumentStatus::Missing->value,
            DocumentStatus::Submitted->value,
            DocumentStatus::UnderReview->value,
            DocumentStatus::Rejected->value,
            DocumentStatus::Expired->value,
        ])->sum(fn (string $status): int => (int) ($documentCounts[$status] ?? 0));

        return [
            $this->item('Dados do candidato', 'completed', 'Candidatura associada a utilizador registado.'),
            $this->item('Agregado', $application->household()->exists() ? 'completed' : 'pending', 'Dados de agregado habitacional associados.'),
            $this->item('Rendimentos', $application->household?->incomeRecords()->exists() ? 'completed' : 'pending', 'Rendimentos declarados no agregado.'),
            $this->item('Documentos obrigatórios', $pendingDocuments > 0 ? 'warning' : 'completed', $pendingDocuments > 0 ? 'Existem documentos pendentes.' : 'Sem pendências documentais detetadas.'),
            $this->item('Elegibilidade', $application->latestEligibilityCheck()->exists() ? 'completed' : 'pending', 'Verificação formal de elegibilidade.'),
            $this->item('Pontuação', $application->latestApplicationScore()->exists() ? 'completed' : 'pending', 'Classificação operacional.'),
            $this->item('Visitas', $application->housingVisits()->exists() ? 'completed' : 'not_applicable', 'Visitas associadas quando aplicável.'),
            $this->item('Audiência', $application->hearings()->exists() ? 'completed' : 'not_applicable', 'Audiência prévia quando aplicável.'),
            $this->item('Reclamações', $application->complaints()->exists() ? 'warning' : 'not_applicable', 'Reclamações associadas quando aplicável.'),
            $this->item('Lista', $application->provisionalListEntries()->exists() || $application->definitiveListEntries()->exists() ? 'completed' : 'pending', 'Entrada em lista provisória/definitiva.'),
            $this->item('Contrato', $application->leaseContracts()->exists() ? 'completed' : 'not_applicable', 'Contrato apenas após atribuição.'),
        ];
    }

    /**
     * @return array{label: string, status: string, description: string}
     */
    private function item(string $label, string $status, string $description): array
    {
        return compact('label', 'status', 'description');
    }
}
