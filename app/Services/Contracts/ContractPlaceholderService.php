<?php

namespace App\Services\Contracts;

use App\Models\Contract;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class ContractPlaceholderService
{
    public function render(string $template, Contract $contract, string $clausesHtml = ''): HtmlString
    {
        $contract->loadMissing(['candidate', 'program.municipality', 'contest', 'housingUnit', 'deposit', 'rentCalculation']);
        $map = $this->placeholderMap($contract, $clausesHtml);
        $html = strtr($template, $map);

        if (preg_match('/{{\s*[^}]+\s*}}/', $html, $matches)) {
            throw ValidationException::withMessages([
                'template_body' => 'Existem placeholders não resolvidos na minuta: '.$matches[0],
            ]);
        }

        return new HtmlString($html);
    }

    /**
     * @return array<string|int, mixed>
     */
    public function placeholderMap(Contract $contract, string $clausesHtml = ''): array
    {
        $date = fn ($value) => $value ? $value->format('d/m/Y') : '-';
        $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' EUR';
        $text = fn ($value) => e((string) ($value ?: '-'));
        $effortRate = data_get($contract->getRelationValue('rentCalculation'), 'calculated_effort_rate_percentage');

        return [
            '{{contract.number}}' => $text($contract->contract_number),
            '{{contract.start_date}}' => $date($contract->start_date),
            '{{contract.end_date}}' => $date($contract->end_date),
            '{{contract.duration_months}}' => $text($contract->duration_months),
            '{{tenant.name}}' => $text($contract->tenant_name),
            '{{tenant.identification_number}}' => $text($contract->tenant_identification_number),
            '{{tenant.tax_number}}' => $text($contract->tenant_tax_number),
            '{{tenant.address}}' => $text($contract->tenant_address),
            '{{housing.address}}' => $text($contract->housing_address),
            '{{housing.typology}}' => $text($contract->housing_typology),
            '{{housing.floor}}' => $text($contract->housing_floor),
            '{{housing.area}}' => $text($contract->housing_area),
            '{{rent.amount}}' => $money($contract->monthly_rent),
            '{{rent.effort_rate}}' => $text($effortRate ? $effortRate.'%' : '-'),
            '{{deposit.amount}}' => $money(data_get($contract->getRelationValue('deposit'), 'amount') ?? $contract->deposit_amount),
            '{{program.name}}' => $text(data_get($contract->getRelationValue('program'), 'name')),
            '{{contest.name}}' => $text(data_get($contract->getRelationValue('contest'), 'title')),
            '{{municipality.name}}' => $text(data_get($contract->getRelationValue('program'), 'municipality.name') ?? $contract->landlord_name),
            '{{clauses}}' => $clausesHtml,
        ];
    }
}
