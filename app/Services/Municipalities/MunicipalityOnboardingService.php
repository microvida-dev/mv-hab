<?php

namespace App\Services\Municipalities;

use App\Data\Municipalities\MunicipalityOnboardingData;
use App\Data\Municipalities\MunicipalityOnboardingPreview;
use App\Data\Municipalities\MunicipalityOnboardingResult;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\MunicipalAdministratorInvitationStatus;
use App\Enums\MunicipalityOnboardingStatus;
use App\Models\MunicipalAdministratorInvitation;
use App\Models\Municipality;
use App\Models\MunicipalityOnboardingRun;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class MunicipalityOnboardingService
{
    public function __construct(
        private readonly MunicipalityOnboardingPlanner $planner,
        private readonly MunicipalAdministratorRoleProvisioningService $roles,
        private readonly PlatformMunicipalRoleAssignmentService $assignments,
        private readonly MunicipalAdministratorInvitationService $invitations,
        private readonly AuditTrailService $audit,
    ) {}

    public function preview(
        MunicipalityOnboardingData $data,
        User $actor,
    ): MunicipalityOnboardingPreview {
        return $this->planner->preview($data, $actor);
    }

    public function onboard(
        MunicipalityOnboardingData $data,
        User $actor,
    ): MunicipalityOnboardingResult {
        $preview = $this->planner->preview($data, $actor);

        if ($preview->idempotentReplay) {
            return $this->existingResult($data->code);
        }

        if ($preview->hasConflicts()) {
            throw new DomainException(
                'O onboarding foi bloqueado pelos conflitos: '.implode(', ', $preview->conflicts).'.',
            );
        }

        $run = $this->acquireRun($data, $actor, $preview);

        if ($run->status === MunicipalityOnboardingStatus::Completed) {
            return $this->existingResult($data->code);
        }

        try {
            $this->audit->record(
                eventCode: 'municipality_onboarding_started',
                auditable: $run,
                category: AuditEventCategory::Security,
                severity: AuditEventSeverity::Notice,
                description: 'Onboarding municipal iniciado.',
                metadata: [
                    'operation_id' => $run->operation_id,
                    'onboarding_run_id' => $run->id,
                    'municipality_code' => $run->municipality_code,
                    'input_fingerprint' => $run->input_fingerprint,
                    'role_template_key' => $run->role_template_key,
                    'role_template_version' => $run->role_template_version,
                ],
                actor: $actor,
                useAuthenticatedUser: false,
            );

            [$run, $municipality, $administrator, $role, $invitation] = DB::transaction(
                function () use ($data, $actor, $run): array {
                    $lockedRun = MunicipalityOnboardingRun::query()
                        ->whereKey($run->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedRun->status !== MunicipalityOnboardingStatus::Processing) {
                        throw new DomainException('O onboarding deixou de estar disponível para processamento.');
                    }

                    $revalidated = $this->planner->preview($data, $actor, $lockedRun->id);

                    if ($revalidated->hasConflicts()
                        || ! hash_equals($lockedRun->input_fingerprint, $revalidated->inputFingerprint)) {
                        throw new DomainException('Os dados do onboarding alteraram-se antes do commit.');
                    }

                    $municipality = Municipality::query()->create([
                        'name' => $data->name,
                        'code' => $data->code,
                        'tax_number' => $data->taxNumber,
                        'contact_email' => $data->contactEmail,
                        'settings' => [
                            'onboarding' => [
                                'operation_id' => $lockedRun->operation_id,
                                'status' => MunicipalityOnboardingStatus::Completed->value,
                                'catalogue_status' => 'pending',
                            ],
                        ],
                        'active' => true,
                    ]);

                    $role = $this->roles->provision($municipality);

                    $administrator = new User;
                    $administrator->municipality()->associate($municipality);
                    $administrator->name = $data->adminName;
                    $administrator->email = $data->adminEmail;
                    $administrator->email_verified_at = now();
                    $administrator->password = Str::password(64);
                    $administrator->status = 'active';
                    $administrator->mfa_required = true;
                    $administrator->save();

                    $this->assignments->assignInitialAdministrator(
                        $actor,
                        $administrator,
                        $role,
                        $data->justification,
                    );

                    $invitation = MunicipalAdministratorInvitation::query()->create([
                        'onboarding_run_id' => $lockedRun->id,
                        'user_id' => $administrator->id,
                        'idempotency_key' => hash(
                            'sha256',
                            'municipal-administrator-invitation:'.$lockedRun->operation_id,
                        ),
                        'status' => MunicipalAdministratorInvitationStatus::Pending,
                        'attempt_count' => 0,
                    ]);

                    $lockedRun->forceFill([
                        'municipality_id' => $municipality->id,
                        'admin_user_id' => $administrator->id,
                        'status' => MunicipalityOnboardingStatus::Completed,
                        'failure_code' => null,
                        'completed_at' => now(),
                        'failed_at' => null,
                    ])->save();

                    $auditMetadata = [
                        'operation_id' => $lockedRun->operation_id,
                        'onboarding_run_id' => $lockedRun->id,
                        'municipality_id' => $municipality->id,
                        'administrator_user_id' => $administrator->id,
                        'role_id' => $role->id,
                        'role_template_key' => $role->template_key,
                        'role_template_version' => $role->template_version,
                        'role_template_fingerprint' => $role->template_fingerprint,
                        'permission_count' => $role->permissions()->count(),
                        'mfa_required' => true,
                        'entitlements_activated' => 0,
                    ];

                    $this->audit->record(
                        eventCode: 'municipality_created',
                        auditable: $municipality,
                        category: AuditEventCategory::Security,
                        severity: AuditEventSeverity::Notice,
                        description: 'Município criado por onboarding controlado.',
                        metadata: $auditMetadata,
                        subject: $administrator,
                        actor: $actor,
                        useAuthenticatedUser: false,
                    );
                    $this->audit->record(
                        eventCode: 'municipal_administrator_role_created',
                        auditable: $role,
                        category: AuditEventCategory::Security,
                        severity: AuditEventSeverity::Notice,
                        description: 'Role administrativa municipal criada por template.',
                        metadata: $auditMetadata,
                        subject: $administrator,
                        actor: $actor,
                        useAuthenticatedUser: false,
                    );
                    $this->audit->record(
                        eventCode: 'municipal_administrator_created',
                        auditable: $administrator,
                        category: AuditEventCategory::Security,
                        severity: AuditEventSeverity::Notice,
                        description: 'Primeiro administrador municipal criado.',
                        metadata: $auditMetadata,
                        subject: $administrator,
                        actor: $actor,
                        useAuthenticatedUser: false,
                    );
                    $this->audit->record(
                        eventCode: 'municipal_administrator_invitation_created',
                        auditable: $invitation,
                        category: AuditEventCategory::Security,
                        severity: AuditEventSeverity::Info,
                        description: 'Intenção de convite administrativo municipal persistida.',
                        metadata: [
                            ...$auditMetadata,
                            'invitation_id' => $invitation->id,
                            'invitation_status' => $invitation->status->value,
                        ],
                        subject: $administrator,
                        actor: $actor,
                        useAuthenticatedUser: false,
                    );
                    $this->audit->record(
                        eventCode: 'municipality_onboarding_completed',
                        auditable: $lockedRun,
                        category: AuditEventCategory::Security,
                        severity: AuditEventSeverity::Notice,
                        description: 'Onboarding municipal concluído.',
                        metadata: [
                            ...$auditMetadata,
                            'invitation_id' => $invitation->id,
                        ],
                        subject: $administrator,
                        actor: $actor,
                        useAuthenticatedUser: false,
                    );

                    return [$lockedRun, $municipality, $administrator, $role, $invitation];
                },
                3,
            );
        } catch (Throwable $exception) {
            $this->markFailed($run, $actor, $exception);

            throw $exception;
        }

        $invitation = $this->invitations->queue($invitation);

        return new MunicipalityOnboardingResult(
            operationId: $run->operation_id,
            runId: (int) $run->id,
            municipalityId: (int) $municipality->id,
            adminUserId: (int) $administrator->id,
            roleId: (int) $role->id,
            invitationId: (int) $invitation->id,
            invitationStatus: $invitation->status->value,
            mfaRequired: true,
            idempotentReplay: false,
        );
    }

    private function acquireRun(
        MunicipalityOnboardingData $data,
        User $actor,
        MunicipalityOnboardingPreview $preview,
    ): MunicipalityOnboardingRun {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction(function () use ($data, $actor, $preview): MunicipalityOnboardingRun {
                    $existing = MunicipalityOnboardingRun::query()
                        ->where('municipality_code', $data->code)
                        ->lockForUpdate()
                        ->first();

                    if ($existing instanceof MunicipalityOnboardingRun) {
                        if ($existing->status === MunicipalityOnboardingStatus::Processing) {
                            throw new DomainException('Já existe um onboarding em processamento.');
                        }

                        if ($existing->status === MunicipalityOnboardingStatus::Completed) {
                            if (hash_equals($existing->input_fingerprint, $preview->inputFingerprint)) {
                                return $existing;
                            }

                            throw new DomainException('O Município já possui onboarding concluído com dados divergentes.');
                        }

                        if (! hash_equals($existing->input_fingerprint, $preview->inputFingerprint)) {
                            throw new DomainException('A repetição de um onboarding falhado exige o mesmo fingerprint.');
                        }

                        $existing->forceFill([
                            'actor_id' => $actor->id,
                            'status' => MunicipalityOnboardingStatus::Processing,
                            'attempt_count' => $existing->attempt_count + 1,
                            'failure_code' => null,
                            'started_at' => now(),
                            'completed_at' => null,
                            'failed_at' => null,
                        ])->save();

                        return $existing;
                    }

                    return MunicipalityOnboardingRun::query()->create([
                        'operation_id' => (string) Str::uuid(),
                        'municipality_code' => $data->code,
                        'actor_id' => $actor->id,
                        'status' => MunicipalityOnboardingStatus::Processing,
                        'input_fingerprint' => $preview->inputFingerprint,
                        'role_template_key' => $preview->roleTemplateKey,
                        'role_template_version' => $preview->roleTemplateVersion,
                        'role_template_fingerprint' => $preview->roleTemplateFingerprint,
                        'attempt_count' => 1,
                        'started_at' => now(),
                    ]);
                });
            } catch (QueryException $exception) {
                if ($attempt >= 2) {
                    throw $exception;
                }
            }
        }
    }

    private function existingResult(string $municipalityCode): MunicipalityOnboardingResult
    {
        $run = MunicipalityOnboardingRun::query()
            ->with('invitation')
            ->where('municipality_code', $municipalityCode)
            ->where('status', MunicipalityOnboardingStatus::Completed)
            ->firstOrFail();
        $role = Role::query()
            ->where('municipality_id', $run->municipality_id)
            ->where('template_key', MunicipalityOnboardingPlanner::TEMPLATE_KEY)
            ->firstOrFail();
        $invitation = $run->invitation;

        if (! $invitation instanceof MunicipalAdministratorInvitation
            || $run->municipality_id === null
            || $run->admin_user_id === null) {
            throw new DomainException('O onboarding concluído não possui referências operacionais completas.');
        }

        return new MunicipalityOnboardingResult(
            operationId: $run->operation_id,
            runId: (int) $run->id,
            municipalityId: (int) $run->municipality_id,
            adminUserId: (int) $run->admin_user_id,
            roleId: (int) $role->id,
            invitationId: (int) $invitation->id,
            invitationStatus: $invitation->status->value,
            mfaRequired: true,
            idempotentReplay: true,
        );
    }

    private function markFailed(
        MunicipalityOnboardingRun $run,
        User $actor,
        Throwable $exception,
    ): void {
        $failureCode = mb_substr(class_basename($exception), 0, 120);

        DB::transaction(function () use ($run, $failureCode): void {
            $locked = MunicipalityOnboardingRun::query()
                ->whereKey($run->id)
                ->lockForUpdate()
                ->first();

            if (! $locked instanceof MunicipalityOnboardingRun
                || $locked->status === MunicipalityOnboardingStatus::Completed) {
                return;
            }

            $locked->forceFill([
                'status' => MunicipalityOnboardingStatus::Failed,
                'failure_code' => $failureCode,
                'failed_at' => now(),
            ])->save();
        });

        $failedRun = MunicipalityOnboardingRun::query()->find($run->id);

        if (! $failedRun instanceof MunicipalityOnboardingRun
            || $failedRun->status !== MunicipalityOnboardingStatus::Failed) {
            return;
        }

        try {
            $this->audit->record(
                eventCode: 'municipality_onboarding_failed',
                auditable: $failedRun,
                category: AuditEventCategory::Security,
                severity: AuditEventSeverity::Critical,
                description: 'O onboarding municipal falhou antes da conclusão do domínio.',
                metadata: [
                    'operation_id' => $failedRun->operation_id,
                    'onboarding_run_id' => $failedRun->id,
                    'municipality_code' => $failedRun->municipality_code,
                    'input_fingerprint' => $failedRun->input_fingerprint,
                    'failure_code' => $failureCode,
                    'attempt_count' => $failedRun->attempt_count,
                ],
                actor: $actor,
                useAuthenticatedUser: false,
            );
        } catch (Throwable) {
            // O ledger de falha permanece a fonte operacional mesmo quando
            // o subsistema de auditoria está indisponível.
        }
    }
}
