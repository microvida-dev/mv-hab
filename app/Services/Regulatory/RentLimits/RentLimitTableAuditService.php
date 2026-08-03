<?php

namespace App\Services\Regulatory\RentLimits;

use App\Data\Regulatory\RentLimitTableAuditResult;
use App\Enums\RentLimitConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentLimitTableManifest;
use App\Models\RentRuleSet;
use App\Support\DecimalMoney;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class RentLimitTableAuditService
{
    public function __construct(
        private readonly RentLimitTableChecksumService $checksums,
    ) {}

    public function audit(
        AffordableRentRegulatoryProfile $profile,
        ?RentRuleSet $ruleSet,
        CarbonInterface $referenceDate,
    ): RentLimitTableAuditResult {
        if (! $ruleSet instanceof RentRuleSet) {
            return $this->missing(
                null,
                ['Não existe um conjunto de regras de renda versionado para o contexto.'],
            );
        }

        if ($ruleSet->regulatory_profile_id !== $profile->id) {
            return $this->missing(
                $ruleSet->id,
                ['O conjunto de regras de renda não pertence ao perfil regulamentar.'],
            );
        }

        $manifest = RentLimitTableManifest::query()
            ->with('rows')
            ->where('regulatory_profile_id', $profile->id)
            ->where('rent_rule_set_id', $ruleSet->id)
            ->first();

        if (! $manifest instanceof RentLimitTableManifest) {
            return $this->missing(
                $ruleSet->id,
                ['Não existe manifesto de proveniência para a tabela de limites de renda.'],
            );
        }

        $rows = $manifest->rows;
        $municipalities = $this->coverage($manifest->municipality_coverage);
        $typologies = $this->coverage($manifest->typology_coverage);
        $actualPairs = $rows
            ->mapWithKeys(fn ($row): array => [
                $this->pair($row->municipality_code, $row->typology) => true,
            ]);
        $missingRows = array_values(collect($municipalities)
            ->crossJoin($typologies)
            ->map(fn (array $pair): string => $this->pair($pair[0], $pair[1]))
            ->reject(fn (string $pair): bool => $actualPairs->has($pair))
            ->sort()
            ->values()
            ->all());
        $calculatedChecksum = $this->checksums->calculate($rows);
        $findings = [];
        $localReferenceDate = CarbonImmutable::instance($referenceDate)
            ->setTimezone('Europe/Lisbon')
            ->startOfDay();

        if (! $profile->rent_limits_configured) {
            $findings[] = 'O perfil não declara limites de renda configurados.';
        }

        if (blank($manifest->source_document)) {
            $findings[] = 'O documento-fonte não está identificado.';
        }

        if (blank($manifest->source_reference)) {
            $findings[] = 'A referência oficial não está identificada.';
        }

        if (blank($manifest->source_version) || $manifest->source_version !== $profile->source_version) {
            $findings[] = 'A versão da fonte não coincide com o perfil regulamentar.';
        }

        if (
            $localReferenceDate->lt($manifest->effective_from)
            || ($manifest->effective_until !== null && $localReferenceDate->gt($manifest->effective_until))
        ) {
            $findings[] = 'A tabela não está vigente na data de referência.';
        }

        if ($manifest->validated_at === null || $manifest->validated_by === null) {
            $findings[] = 'A validação técnica do manifesto não está completa.';
        }

        if ($rows->isEmpty() || $manifest->row_count !== $rows->count()) {
            $findings[] = 'A contagem declarada não coincide com as linhas instaladas.';
        }

        if ($municipalities === [] || $typologies === []) {
            $findings[] = 'A cobertura municipal ou tipológica não está declarada.';
        }

        if ($missingRows !== []) {
            $findings[] = 'Existem combinações Município/tipologia em falta.';
        }

        if (blank($manifest->checksum) || ! hash_equals((string) $manifest->checksum, $calculatedChecksum)) {
            $findings[] = 'O checksum declarado não coincide com o conteúdo instalado.';
        }

        if ($manifest->demo_only && ! config('mvhab.regulatory_demo_mode', false)) {
            $findings[] = 'Dados de demonstração não são válidos fora do modo demo explícito.';
        }

        $aggregateMinimum = $rows
            ->pluck('minimum_rent')
            ->filter(fn (mixed $value): bool => $value !== null)
            ->map(fn (mixed $value): string => DecimalMoney::normalize((string) $value))
            ->sort(fn (string $left, string $right): int => DecimalMoney::compare($left, $right))
            ->first();
        $aggregateMaximum = $rows
            ->pluck('maximum_rent')
            ->map(fn (mixed $value): string => DecimalMoney::normalize((string) $value))
            ->sort(fn (string $left, string $right): int => DecimalMoney::compare($left, $right))
            ->last();
        $ruleMinimum = $ruleSet->minimum_rent === null
            ? null
            : DecimalMoney::normalize($ruleSet->minimum_rent);
        $ruleMaximum = $ruleSet->maximum_rent === null
            ? null
            : DecimalMoney::normalize($ruleSet->maximum_rent);

        if ($ruleMinimum !== $aggregateMinimum || $ruleMaximum !== $aggregateMaximum) {
            $findings[] = 'Os limites agregados não coincidem com o conjunto de regras de renda.';
        }

        $status = match (true) {
            $manifest->validation_status === RentLimitConfigurationStatus::RequiresManualReview => RentLimitConfigurationStatus::RequiresManualReview,
            $manifest->validation_status !== RentLimitConfigurationStatus::Configured
                || $findings !== [] => RentLimitConfigurationStatus::Incomplete,
            default => RentLimitConfigurationStatus::Configured,
        };

        return new RentLimitTableAuditResult(
            status: $status,
            manifestId: $manifest->id,
            rentRuleSetId: $ruleSet->id,
            sourceDocument: $manifest->source_document,
            sourceReference: $manifest->source_reference,
            sourceVersion: $manifest->source_version,
            effectiveFrom: $manifest->effective_from->toDateString(),
            effectiveUntil: $manifest->effective_until?->toDateString(),
            declaredChecksum: $manifest->checksum,
            calculatedChecksum: $calculatedChecksum,
            declaredRowCount: $manifest->row_count,
            actualRowCount: $rows->count(),
            municipalities: $municipalities,
            typologies: $typologies,
            missingRows: $missingRows,
            findings: $findings,
            minimumRent: $aggregateMinimum,
            maximumRent: $aggregateMaximum,
            demoOnly: $manifest->demo_only,
        );
    }

    /**
     * @param  list<string>  $findings
     */
    private function missing(?int $ruleSetId, array $findings): RentLimitTableAuditResult
    {
        return new RentLimitTableAuditResult(
            status: RentLimitConfigurationStatus::Incomplete,
            manifestId: null,
            rentRuleSetId: $ruleSetId,
            sourceDocument: null,
            sourceReference: null,
            sourceVersion: null,
            effectiveFrom: null,
            effectiveUntil: null,
            declaredChecksum: null,
            calculatedChecksum: null,
            declaredRowCount: 0,
            actualRowCount: 0,
            municipalities: [],
            typologies: [],
            missingRows: [],
            findings: $findings,
        );
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function coverage(array $values): array
    {
        return array_values(collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => strtoupper(trim($value)))
            ->unique()
            ->sort()
            ->values()
            ->all());
    }

    private function pair(string $municipality, string $typology): string
    {
        return strtoupper(trim($municipality)).'|'.strtoupper(trim($typology));
    }
}
