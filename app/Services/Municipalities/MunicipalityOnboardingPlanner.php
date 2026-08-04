<?php

namespace App\Services\Municipalities;

use App\Data\Municipalities\MunicipalityOnboardingData;
use App\Data\Municipalities\MunicipalityOnboardingPreview;
use App\Enums\MunicipalityOnboardingConflict;
use App\Enums\MunicipalityOnboardingStatus;
use App\Models\Municipality;
use App\Models\MunicipalityOnboardingRun;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Security\MfaEnforcementService;
use App\Services\Support\CanonicalJsonHasher;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

final class MunicipalityOnboardingPlanner
{
    public const TEMPLATE_KEY = 'municipal-administrator';

    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly MfaEnforcementService $mfa,
        private readonly MunicipalRoleTemplateRegistry $templates,
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    public function preview(
        MunicipalityOnboardingData $data,
        User $actor,
        ?int $ignoreRunId = null,
    ): MunicipalityOnboardingPreview {
        $this->assertActor($actor);
        $template = $this->templates->resolve(self::TEMPLATE_KEY);

        if (in_array('*', $template['permissions'], true)
            || collect($template['permissions'])->contains(
                fn (string $permission): bool => str_contains($permission, '*'),
            )) {
            throw new DomainException('O template administrativo municipal não pode conter wildcards.');
        }

        if ($template['entitlement_dependencies'] !== []) {
            throw new DomainException('O template administrativo municipal não pode ativar ou exigir entitlements.');
        }

        $fingerprint = $this->hasher->hash([
            'municipality' => [
                'name' => $data->name,
                'code' => $data->code,
                'tax_number' => $data->taxNumber,
                'contact_email' => $data->contactEmail,
            ],
            'administrator' => [
                'name' => $data->adminName,
                'email' => $data->adminEmail,
                'mfa_required' => true,
            ],
            'template' => [
                'key' => $template['key'],
                'version' => $template['version'],
                'fingerprint' => $template['fingerprint'],
            ],
        ]);

        $existingRun = MunicipalityOnboardingRun::query()
            ->where('municipality_code', $data->code)
            ->when($ignoreRunId !== null, fn ($query) => $query->whereKeyNot($ignoreRunId))
            ->first();

        if ($existingRun instanceof MunicipalityOnboardingRun
            && $existingRun->status === MunicipalityOnboardingStatus::Completed
            && hash_equals($existingRun->input_fingerprint, $fingerprint)) {
            return new MunicipalityOnboardingPreview(
                operationId: (string) Str::uuid(),
                inputFingerprint: $fingerprint,
                municipalityCode: $data->code,
                roleTemplateKey: $template['key'],
                roleTemplateVersion: $template['version'],
                roleTemplateFingerprint: $template['fingerprint'],
                permissionCount: count($template['permissions']),
                mfaRequired: true,
                conflicts: [],
                idempotentReplay: true,
            );
        }

        $conflicts = [];

        if ($existingRun instanceof MunicipalityOnboardingRun) {
            if ($existingRun->status === MunicipalityOnboardingStatus::Processing) {
                $conflicts[] = MunicipalityOnboardingConflict::OnboardingInProgress->value;
            } elseif (! hash_equals($existingRun->input_fingerprint, $fingerprint)) {
                $conflicts[] = MunicipalityOnboardingConflict::OnboardingFingerprintMismatch->value;
            }
        }

        if (Municipality::query()->where('code', $data->code)->exists()) {
            $conflicts[] = MunicipalityOnboardingConflict::MunicipalityCodeExists->value;
        }

        if (Municipality::query()->where('tax_number', $data->taxNumber)->exists()) {
            $conflicts[] = MunicipalityOnboardingConflict::MunicipalityTaxNumberExists->value;
        }

        if (Municipality::query()
            ->whereRaw('LOWER(contact_email) = ?', [$data->contactEmail])
            ->exists()) {
            $conflicts[] = MunicipalityOnboardingConflict::MunicipalityContactEmailExists->value;
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$data->adminEmail])->exists()) {
            $conflicts[] = MunicipalityOnboardingConflict::AdministratorEmailExists->value;
        }

        return new MunicipalityOnboardingPreview(
            operationId: (string) Str::uuid(),
            inputFingerprint: $fingerprint,
            municipalityCode: $data->code,
            roleTemplateKey: $template['key'],
            roleTemplateVersion: $template['version'],
            roleTemplateFingerprint: $template['fingerprint'],
            permissionCount: count($template['permissions']),
            mfaRequired: true,
            conflicts: array_values(array_unique($conflicts)),
            idempotentReplay: false,
        );
    }

    public function assertActor(User $actor): void
    {
        if (($actor->status ?? 'active') !== 'active'
            || $actor->municipality_id !== null
            || $actor->hasRole(['candidate', 'auditor'])
            || ! $this->platformScope->hasGlobalScope($actor)
            || ! $actor->hasPermission('municipalities.create')) {
            throw new AuthorizationException(
                'A operação exige um operador global ativo com autorização para criar Municípios.',
            );
        }

        if (! $actor->mfa_required || ! $this->mfa->hasConfirmedDevice($actor)) {
            throw new AuthorizationException(
                'A operação exige MFA obrigatório e um dispositivo confirmado.',
            );
        }
    }

    public function roleIdentifier(int $municipalityId): string
    {
        return Str::limit(
            'municipal_'.$municipalityId.'_'.Str::slug(self::TEMPLATE_KEY, '_'),
            180,
            '',
        );
    }
}
