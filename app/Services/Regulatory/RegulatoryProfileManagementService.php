<?php

namespace App\Services\Regulatory;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryProfileStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Platform\PlatformMunicipalContextService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Support\AuditEvents;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RegulatoryProfileManagementService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly MunicipalRegulatoryOverlayService $overlayService,
        private readonly PlatformMunicipalContextService $municipalContext,
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): AffordableRentRegulatoryProfile
    {
        $this->assertGlobalActor($actor);

        return DB::transaction(function () use ($data, $actor): AffordableRentRegulatoryProfile {
            $normalized = $this->normalized($data, $actor);
            $this->assertUniqueCodeVersion((string) $normalized['code'], (string) $normalized['version']);

            $profile = AffordableRentRegulatoryProfile::query()->create([
                ...$normalized,
                'status' => RegulatoryProfileStatus::Draft->value,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->overlayService->assertValid($profile->load('parentProfile'));

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $profile,
                'programs',
                'regulatory_profile_create',
                'Perfil regulamentar criado.',
                newValues: $this->auditValues($profile),
            );

            return $profile->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        AffordableRentRegulatoryProfile $profile,
        array $data,
        User $actor,
    ): AffordableRentRegulatoryProfile {
        $this->assertGlobalActor($actor);

        return DB::transaction(function () use ($profile, $data, $actor): AffordableRentRegulatoryProfile {
            $locked = AffordableRentRegulatoryProfile::query()
                ->whereKey($profile->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertMutable($locked);

            $before = $this->auditValues($locked);
            $normalized = $this->normalized($data, $actor);
            $this->assertUniqueCodeVersion((string) $normalized['code'], (string) $normalized['version'], $locked);

            $locked->update([
                ...$normalized,
                'updated_by' => $actor->id,
            ]);
            $locked->refresh()->load('parentProfile');
            $this->overlayService->assertValid($locked);

            if ($locked->status === RegulatoryProfileStatus::Active) {
                $this->assertNoActiveOverlap($locked);
            }

            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $locked,
                'programs',
                'regulatory_profile_update',
                'Perfil regulamentar atualizado.',
                oldValues: $before,
                newValues: $this->auditValues($locked),
            );

            return $locked->refresh();
        });
    }

    public function activate(AffordableRentRegulatoryProfile $profile, User $actor): AffordableRentRegulatoryProfile
    {
        $this->assertGlobalActor($actor);

        return DB::transaction(function () use ($profile, $actor): AffordableRentRegulatoryProfile {
            $locked = AffordableRentRegulatoryProfile::query()
                ->whereKey($profile->getKey())
                ->lockForUpdate()
                ->with('parentProfile')
                ->firstOrFail();
            $this->assertMutable($locked);
            $this->overlayService->assertValid($locked);

            if (
                $locked->parentProfile instanceof AffordableRentRegulatoryProfile
                && $locked->parentProfile->status !== RegulatoryProfileStatus::Active
            ) {
                throw ValidationException::withMessages([
                    'parent_profile_id' => 'Ative primeiro o perfil regulamentar nacional de origem.',
                ]);
            }

            $this->assertNoActiveOverlap($locked);

            $before = $this->auditValues($locked);
            $locked->forceFill([
                'status' => RegulatoryProfileStatus::Active->value,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(
                AuditEvents::APPROVE,
                $locked,
                'programs',
                'regulatory_profile_activate',
                'Perfil regulamentar ativado.',
                oldValues: $before,
                newValues: $this->auditValues($locked),
            );

            return $locked->refresh();
        });
    }

    public function archive(AffordableRentRegulatoryProfile $profile, User $actor): AffordableRentRegulatoryProfile
    {
        $this->assertGlobalActor($actor);

        return DB::transaction(function () use ($profile, $actor): AffordableRentRegulatoryProfile {
            $locked = AffordableRentRegulatoryProfile::query()
                ->whereKey($profile->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->snapshots()->exists()) {
                throw ValidationException::withMessages([
                    'profile' => 'O perfil possui snapshots regulamentares. Mantenha-o ativo ou crie uma nova versão; não o arquive durante procedimentos já publicados.',
                ]);
            }

            $before = $this->auditValues($locked);
            $locked->forceFill([
                'status' => RegulatoryProfileStatus::Archived->value,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $locked,
                'programs',
                'regulatory_profile_archive',
                'Perfil regulamentar arquivado.',
                oldValues: $before,
                newValues: $this->auditValues($locked),
            );

            return $locked->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data, User $actor): array
    {
        $scope = (string) Arr::pull($data, 'scope_type');
        $municipality = $this->municipalContext->requireMunicipality($actor);
        $isMunicipal = $scope === 'municipal';

        $data['municipality_id'] = $isMunicipal ? $municipality->id : null;
        $data['parent_profile_id'] = $isMunicipal
            ? ($data['parent_profile_id'] ?? null)
            : null;
        $data['legal_regime'] = AffordableRentLegalRegime::from((string) $data['legal_regime'])->value;
        $data['configuration_status'] = RegulatoryConfigurationStatus::from((string) $data['configuration_status'])->value;
        $data['rent_limits_configured'] = (bool) ($data['rent_limits_configured'] ?? false);
        $data['eligibility_rules_configured'] = (bool) ($data['eligibility_rules_configured'] ?? false);
        $data['typology_rules_configured'] = (bool) ($data['typology_rules_configured'] ?? false);
        $data['contract_terms_configured'] = (bool) ($data['contract_terms_configured'] ?? false);
        $data['metadata'] = [
            'catalogue_type' => $isMunicipal ? 'municipal_overlay' : 'national',
            'managed_via_backoffice' => true,
            'municipal_context_id' => $municipality->id,
        ];

        if ($isMunicipal) {
            $parent = AffordableRentRegulatoryProfile::query()
                ->whereNull('municipality_id')
                ->find($data['parent_profile_id']);

            if (! $parent instanceof AffordableRentRegulatoryProfile) {
                throw ValidationException::withMessages([
                    'parent_profile_id' => 'Selecione um perfil nacional válido como base do enquadramento municipal.',
                ]);
            }

            if ($parent->legal_regime->value !== $data['legal_regime']) {
                throw ValidationException::withMessages([
                    'parent_profile_id' => 'O perfil nacional selecionado pertence a outro regime legal.',
                ]);
            }

            foreach ([
                'rent_limits_configured',
                'eligibility_rules_configured',
                'typology_rules_configured',
                'contract_terms_configured',
            ] as $field) {
                if ($parent->{$field}) {
                    $data[$field] = true;
                }
            }
        }

        return $data;
    }

    private function assertGlobalActor(User $actor): void
    {
        if (! $this->platformScope->hasGlobalScope($actor)) {
            throw ValidationException::withMessages([
                'profile' => 'A configuração regulamentar está reservada à administração global da plataforma.',
            ]);
        }
    }

    private function assertMutable(AffordableRentRegulatoryProfile $profile): void
    {
        if ($profile->snapshots()->exists()) {
            throw ValidationException::withMessages([
                'profile' => 'Este perfil já foi fixado em publicações. Crie uma nova versão em vez de alterar a configuração histórica.',
            ]);
        }

        if ($profile->status === RegulatoryProfileStatus::Archived) {
            throw ValidationException::withMessages([
                'profile' => 'Um perfil arquivado não pode ser alterado. Crie uma nova versão.',
            ]);
        }
    }

    private function assertUniqueCodeVersion(
        string $code,
        string $version,
        ?AffordableRentRegulatoryProfile $ignore = null,
    ): void {
        $query = AffordableRentRegulatoryProfile::withTrashed()
            ->where('code', $code)
            ->where('version', $version);

        if ($ignore instanceof AffordableRentRegulatoryProfile) {
            $query->whereKeyNot($ignore->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'version' => 'Já existe um perfil regulamentar com este código e esta versão.',
            ]);
        }
    }

    private function assertNoActiveOverlap(AffordableRentRegulatoryProfile $profile): void
    {
        $from = CarbonImmutable::parse($profile->effective_from->toDateString());
        $until = $profile->effective_until === null
            ? null
            : CarbonImmutable::parse($profile->effective_until->toDateString());
        $untilDate = $until?->toDateString();

        $query = AffordableRentRegulatoryProfile::query()
            ->whereKeyNot($profile->getKey())
            ->where('status', RegulatoryProfileStatus::Active->value)
            ->where('legal_regime', $profile->legal_regime->value)
            ->when(
                $profile->municipality_id === null,
                fn ($builder) => $builder->whereNull('municipality_id'),
                fn ($builder) => $builder->where('municipality_id', $profile->municipality_id),
            )
            ->when(
                $untilDate !== null,
                fn ($builder) => $builder->whereDate('effective_from', '<=', $untilDate),
            )
            ->where(fn ($builder) => $builder
                ->whereNull('effective_until')
                ->orWhereDate('effective_until', '>=', $from->toDateString()));

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'Existe outro perfil ativo do mesmo regime e âmbito com vigência sobreposta.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(AffordableRentRegulatoryProfile $profile): array
    {
        return $profile->only([
            'municipality_id',
            'parent_profile_id',
            'legal_regime',
            'code',
            'version',
            'name',
            'status',
            'configuration_status',
            'effective_from',
            'effective_until',
            'publication_reference',
            'source_version',
            'rent_limits_configured',
            'eligibility_rules_configured',
            'typology_rules_configured',
            'contract_terms_configured',
        ]);
    }
}
