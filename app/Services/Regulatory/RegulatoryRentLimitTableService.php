<?php

namespace App\Services\Regulatory;

use App\Enums\RentLimitConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentLimitTableManifest;
use App\Models\RentRuleSet;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Platform\PlatformMunicipalContextService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Regulatory\RentLimits\RentLimitTableAuditService;
use App\Services\Regulatory\RentLimits\RentLimitTableChecksumService;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegulatoryRentLimitTableService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PlatformMunicipalContextService $municipalContext,
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly RentLimitTableChecksumService $checksums,
        private readonly RentLimitTableAuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(
        AffordableRentRegulatoryProfile $profile,
        array $data,
        User $actor,
    ): RentLimitTableManifest {
        if (! $this->platformScope->hasGlobalScope($actor)) {
            throw ValidationException::withMessages([
                'rent_limits' => 'A configuração da tabela de rendas está reservada à administração global.',
            ]);
        }

        $municipality = $this->municipalContext->requireMunicipality($actor);

        return DB::transaction(function () use ($profile, $data, $actor, $municipality): RentLimitTableManifest {
            $lockedProfile = AffordableRentRegulatoryProfile::query()
                ->whereKey($profile->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedProfile->snapshots()->exists()) {
                throw ValidationException::withMessages([
                    'rent_limits' => 'O perfil já foi utilizado em publicações. Crie uma nova versão para alterar limites de renda.',
                ]);
            }

            if ($lockedProfile->municipality_id !== null && (int) $lockedProfile->municipality_id !== (int) $municipality->id) {
                throw ValidationException::withMessages([
                    'rent_limits' => 'O perfil regulamentar não pertence ao Município atualmente selecionado.',
                ]);
            }

            if (blank($lockedProfile->source_version)) {
                throw ValidationException::withMessages([
                    'source_version' => 'Defina primeiro a versão da fonte oficial no perfil regulamentar.',
                ]);
            }

            $ruleSet = RentRuleSet::query()
                ->whereKey((int) $data['rent_rule_set_id'])
                ->where('regulatory_profile_id', $lockedProfile->id)
                ->whereHas('program', fn ($query) => $query->where('municipality_id', $municipality->id))
                ->lockForUpdate()
                ->first();

            if (! $ruleSet instanceof RentRuleSet) {
                throw ValidationException::withMessages([
                    'rent_rule_set_id' => 'Selecione um conjunto de regras de renda do perfil e do Município atuais.',
                ]);
            }

            $rawRows = $data['rows'] ?? [];
            if (! is_array($rawRows)) {
                throw ValidationException::withMessages([
                    'rows' => 'A tabela de rendas tem um formato inválido.',
                ]);
            }

            /**
             * @var list<array{
             *     municipality_code: string,
             *     typology: string,
             *     minimum_rent: string|null,
             *     maximum_rent: string,
             *     source_row_reference: string|null
             * }> $rows
             */
            $rows = [];
            $typologies = [];

            foreach ($rawRows as $index => $row) {
                if (! is_array($row) || blank($row['typology'] ?? null)) {
                    continue;
                }

                $typology = strtoupper(trim((string) $row['typology']));
                $minimum = filled($row['minimum_rent'] ?? null)
                    ? DecimalMoney::normalize((string) $row['minimum_rent'])
                    : null;
                $maximum = filled($row['maximum_rent'] ?? null)
                    ? DecimalMoney::normalize((string) $row['maximum_rent'])
                    : null;

                if ($maximum === null) {
                    throw ValidationException::withMessages([
                        "rows.{$index}.maximum_rent" => 'Defina o limite máximo de renda para cada tipologia utilizada.',
                    ]);
                }

                if ($minimum !== null && DecimalMoney::compare($minimum, $maximum) > 0) {
                    throw ValidationException::withMessages([
                        "rows.{$index}.maximum_rent" => 'A renda máxima não pode ser inferior à renda mínima.',
                    ]);
                }

                if (isset($typologies[$typology])) {
                    throw ValidationException::withMessages([
                        "rows.{$index}.typology" => 'Cada tipologia só pode surgir uma vez na tabela do Município.',
                    ]);
                }
                $typologies[$typology] = true;

                $rows[] = [
                    'municipality_code' => strtoupper($municipality->code),
                    'typology' => $typology,
                    'minimum_rent' => $minimum,
                    'maximum_rent' => $maximum,
                    'source_row_reference' => filled($row['source_row_reference'] ?? null)
                        ? trim((string) $row['source_row_reference'])
                        : null,
                ];
            }

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'rows' => 'Introduza pelo menos uma tipologia e o respetivo limite máximo de renda.',
                ]);
            }

            $manifest = RentLimitTableManifest::query()->updateOrCreate(
                ['rent_rule_set_id' => $ruleSet->id],
                [
                    'regulatory_profile_id' => $lockedProfile->id,
                    'source_document' => trim((string) $data['source_document']),
                    'source_reference' => trim((string) $data['source_reference']),
                    'source_version' => (string) $lockedProfile->source_version,
                    'effective_from' => $data['effective_from'],
                    'effective_until' => $data['effective_until'] ?? null,
                    'row_count' => count($rows),
                    'municipality_coverage' => [strtoupper($municipality->code)],
                    'typology_coverage' => array_keys($typologies),
                    'validation_status' => RentLimitConfigurationStatus::Configured->value,
                    'demo_only' => false,
                    'validated_at' => now(),
                    'validated_by' => $actor->id,
                ],
            );

            $manifest->rows()->delete();
            $manifest->rows()->createMany($rows);
            $manifest->load('rows');
            $manifest->forceFill([
                'checksum' => $this->checksums->calculate($manifest->rows),
            ])->save();

            $minimum = null;
            $maximum = null;
            foreach ($rows as $row) {
                if ($row['minimum_rent'] !== null) {
                    $minimum = $minimum === null
                        ? $row['minimum_rent']
                        : DecimalMoney::min($minimum, $row['minimum_rent']);
                }
                $maximum = $maximum === null
                    ? $row['maximum_rent']
                    : DecimalMoney::max($maximum, $row['maximum_rent']);
            }

            $ruleSet->forceFill([
                'minimum_rent' => $minimum,
                'maximum_rent' => $maximum,
                'updated_by' => $actor->id,
            ])->save();

            $lockedProfile->forceFill([
                'rent_limits_configured' => true,
                'updated_by' => $actor->id,
            ])->save();

            $referenceDate = CarbonImmutable::parse((string) $data['effective_from'], 'Europe/Lisbon');
            $audit = $this->auditService->audit($lockedProfile->refresh(), $ruleSet->refresh(), $referenceDate);

            if ($audit->status !== RentLimitConfigurationStatus::Configured) {
                throw ValidationException::withMessages([
                    'rent_limits' => implode(' ', $audit->findings),
                ]);
            }

            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $manifest,
                'programs',
                'regulatory_rent_limits_update',
                'Tabela regulamentar de limites de renda configurada.',
                newValues: [
                    'regulatory_profile_id' => $lockedProfile->id,
                    'rent_rule_set_id' => $ruleSet->id,
                    'municipality_id' => $municipality->id,
                    'row_count' => $manifest->row_count,
                    'checksum' => $manifest->checksum,
                ],
            );

            return $manifest->refresh()->load('rows');
        });
    }
}
