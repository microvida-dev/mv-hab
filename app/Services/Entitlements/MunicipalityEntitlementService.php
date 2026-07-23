<?php

namespace App\Services\Entitlements;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\FeatureKey;
use App\Exceptions\FeatureDependencyException;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MunicipalityEntitlementService
{
    /** @var array<int, array<string, true>> */
    private array $enabledByMunicipality = [];

    public function __construct(private readonly AuditTrailService $audit) {}

    public function enabledFor(Municipality $municipality, FeatureKey $feature): bool
    {
        return isset($this->enabledFeatureMap($municipality)[$feature->value]);
    }

    public function enabledForUser(User $user, FeatureKey $feature): bool
    {
        $user->loadMissing('municipality');

        return $user->municipality !== null
            && $this->enabledFor($user->municipality, $feature);
    }

    /** @return Collection<int, FeatureKey> */
    public function activeFor(Municipality $municipality): Collection
    {
        return collect(array_keys($this->enabledFeatureMap($municipality)))
            ->map(fn (string $feature): FeatureKey => FeatureKey::from($feature))
            ->values();
    }

    /**
     * @throws FeatureDependencyException
     */
    public function enableFor(
        Municipality $municipality,
        FeatureKey $feature,
        User $actor,
        string $justification,
    ): MunicipalityFeatureEntitlement {
        $justification = $this->validatedJustification($justification);

        return DB::transaction(function () use ($municipality, $feature, $actor, $justification): MunicipalityFeatureEntitlement {
            $lockedMunicipality = Municipality::query()
                ->lockForUpdate()
                ->findOrFail($municipality->getKey());
            $current = $this->currentEnabledFeatures($lockedMunicipality);

            foreach ($feature->dependencies() as $dependency) {
                if (! in_array($dependency, $current, true)) {
                    throw new FeatureDependencyException(
                        sprintf('Não é possível ativar %s sem ativar primeiro %s.', $feature->label(), $dependency->label()),
                    );
                }
            }

            $entitlement = MunicipalityFeatureEntitlement::query()->updateOrCreate(
                [
                    'municipality_id' => $lockedMunicipality->getKey(),
                    'feature_key' => $feature->value,
                ],
                ['enabled' => true],
            );

            $before = in_array($feature, $current, true);
            if (! $before) {
                $this->auditChange($lockedMunicipality, $feature, $actor, $justification, false, true);
            }

            $this->forget($lockedMunicipality);

            return $entitlement->refresh();
        });
    }

    /**
     * @throws FeatureDependencyException
     */
    public function disableFor(
        Municipality $municipality,
        FeatureKey $feature,
        User $actor,
        string $justification,
    ): MunicipalityFeatureEntitlement {
        $justification = $this->validatedJustification($justification);

        return DB::transaction(function () use ($municipality, $feature, $actor, $justification): MunicipalityFeatureEntitlement {
            $lockedMunicipality = Municipality::query()
                ->lockForUpdate()
                ->findOrFail($municipality->getKey());
            $current = $this->currentEnabledFeatures($lockedMunicipality);

            $activeDependants = collect(FeatureKey::cases())
                ->filter(fn (FeatureKey $candidate): bool => in_array($feature, $candidate->dependencies(), true))
                ->filter(fn (FeatureKey $candidate): bool => in_array($candidate, $current, true))
                ->values();

            if ($activeDependants->isNotEmpty()) {
                throw new FeatureDependencyException(sprintf(
                    'Não é possível desativar %s enquanto %s estiver ativa.',
                    $feature->label(),
                    $activeDependants->map(fn (FeatureKey $candidate): string => $candidate->label())->join(' ou '),
                ));
            }

            $entitlement = MunicipalityFeatureEntitlement::query()->updateOrCreate(
                [
                    'municipality_id' => $lockedMunicipality->getKey(),
                    'feature_key' => $feature->value,
                ],
                ['enabled' => false],
            );

            $before = in_array($feature, $current, true);
            if ($before) {
                $this->auditChange($lockedMunicipality, $feature, $actor, $justification, true, false);
            }

            $this->forget($lockedMunicipality);

            return $entitlement->refresh();
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function ensureEnabledFor(Municipality $municipality, FeatureKey $feature): void
    {
        if (! $this->enabledFor($municipality, $feature)) {
            throw new AuthorizationException('Esta funcionalidade não está disponível para o Município atual.');
        }
    }

    /** @return array<string, true> */
    private function enabledFeatureMap(Municipality $municipality): array
    {
        $municipalityId = (int) $municipality->getKey();

        if (! array_key_exists($municipalityId, $this->enabledByMunicipality)) {
            $this->enabledByMunicipality[$municipalityId] = MunicipalityFeatureEntitlement::query()
                ->forMunicipality($municipality)
                ->enabled()
                ->pluck('feature_key')
                ->mapWithKeys(fn (mixed $feature): array => [
                    $feature instanceof FeatureKey ? $feature->value : (string) $feature => true,
                ])
                ->all();
        }

        return $this->enabledByMunicipality[$municipalityId];
    }

    /** @return list<FeatureKey> */
    private function currentEnabledFeatures(Municipality $municipality): array
    {
        return MunicipalityFeatureEntitlement::query()
            ->forMunicipality($municipality)
            ->enabled()
            ->lockForUpdate()
            ->pluck('feature_key')
            ->map(fn (mixed $feature): FeatureKey => $feature instanceof FeatureKey
                ? $feature
                : FeatureKey::from((string) $feature))
            ->all();
    }

    private function forget(Municipality $municipality): void
    {
        unset($this->enabledByMunicipality[(int) $municipality->getKey()]);
    }

    private function validatedJustification(string $justification): string
    {
        $justification = trim($justification);

        if (mb_strlen($justification) < 10 || mb_strlen($justification) > 1000) {
            throw new InvalidArgumentException('A justificação deve ter entre 10 e 1000 caracteres.');
        }

        if ($justification !== strip_tags($justification)) {
            throw new InvalidArgumentException('A justificação não pode conter HTML.');
        }

        return $justification;
    }

    private function auditChange(
        Municipality $municipality,
        FeatureKey $feature,
        User $actor,
        string $justification,
        bool $before,
        bool $after,
    ): void {
        $this->audit->record(
            eventCode: $after ? 'municipality_feature_enabled' : 'municipality_feature_disabled',
            auditable: $municipality,
            category: AuditEventCategory::Security,
            severity: AuditEventSeverity::Info,
            description: $after
                ? 'Funcionalidade municipal ativada.'
                : 'Funcionalidade municipal desativada.',
            oldValues: ['enabled' => $before],
            newValues: ['enabled' => $after],
            metadata: [
                'municipality_id' => $municipality->getKey(),
                'feature_key' => $feature->value,
                'before' => $before,
                'after' => $after,
                'dependencies' => array_map(
                    fn (FeatureKey $dependency): string => $dependency->value,
                    $feature->dependencies(),
                ),
                'actor' => $actor->getKey(),
                'justification' => $justification,
            ],
            actor: $actor,
            useAuthenticatedUser: false,
        );
    }
}
