<?php

namespace App\Services\Analytics;

use App\Data\Analytics\ChartDatasetData;

class ChartDatasetService
{
    /**
     * @param  array<string, int|float>  $counts
     * @return array{type: string, title: string, description: string, items: list<array{label: string, value: int|float}>, total: int|float}
     */
    public function fromKeyedCounts(string $type, string $title, string $description, array $counts, int $limit = 8): array
    {
        arsort($counts);

        $items = [];
        foreach (array_slice($counts, 0, $limit, true) as $label => $value) {
            $items[] = [
                'label' => $this->humanize((string) $label),
                'value' => is_float($value) ? $value : (int) $value,
            ];
        }

        return (new ChartDatasetData($type, $title, $description, $items))->toArray();
    }

    private function humanize(string $label): string
    {
        if ($label === '') {
            return 'Sem classificação';
        }

        $knownLabels = [
            'draft' => 'Rascunho',
            'submitted' => 'Submetida',
            'under_review' => 'Em análise',
            'requires_correction' => 'Aguarda correção',
            'correction_submitted' => 'Correção submetida',
            'eligible' => 'Elegível',
            'ineligible' => 'Não elegível',
            'excluded' => 'Excluída',
            'cancelled' => 'Cancelada',
            'withdrawn' => 'Desistida',
            'expired' => 'Expirada',
            'missing' => 'Em falta',
            'validated' => 'Validado',
            'rejected' => 'Rejeitado',
            'open' => 'Aberto',
            'assigned' => 'Atribuído',
            'waiting_staff' => 'A aguardar técnico',
            'waiting_candidate' => 'A aguardar candidato',
            'resolved' => 'Resolvido',
            'closed' => 'Fechado',
            'pendentes' => 'Pendentes',
            'tratados' => 'Tratados',
        ];

        if (isset($knownLabels[$label])) {
            return $knownLabels[$label];
        }

        return str($label)
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();
    }
}
