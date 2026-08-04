<?php

namespace App\Services\Municipalities;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\MunicipalAdministratorInvitationStatus;
use App\Enums\MunicipalityOnboardingStatus;
use App\Jobs\SendMunicipalAdministratorInvitation;
use App\Models\MunicipalAdministratorInvitation;
use App\Models\MunicipalityOnboardingRun;
use App\Models\User;
use App\Notifications\MunicipalAdministratorInvitationNotification;
use App\Services\Audit\AuditTrailService;
use DomainException;
use Illuminate\Auth\Passwords\PasswordBroker as ConcretePasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Throwable;

final class MunicipalAdministratorInvitationService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function queue(MunicipalAdministratorInvitation $invitation): MunicipalAdministratorInvitation
    {
        $invitation = DB::transaction(function () use ($invitation): MunicipalAdministratorInvitation {
            $locked = MunicipalAdministratorInvitation::query()
                ->whereKey($invitation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($locked->status, [
                MunicipalAdministratorInvitationStatus::Sent,
                MunicipalAdministratorInvitationStatus::Consumed,
                MunicipalAdministratorInvitationStatus::Expired,
            ], true)) {
                return $locked;
            }

            $locked->forceFill([
                'status' => MunicipalAdministratorInvitationStatus::Queued,
                'queued_at' => now(),
                'failed_at' => null,
                'last_failure_code' => null,
            ])->save();

            return $locked;
        });

        if ($invitation->status === MunicipalAdministratorInvitationStatus::Queued) {
            try {
                SendMunicipalAdministratorInvitation::dispatch($invitation->id)
                    ->afterCommit();
            } catch (Throwable $exception) {
                $this->markFailed($invitation->id, $exception);
            }
        }

        return $invitation->refresh();
    }

    public function send(int $invitationId): void
    {
        $context = DB::transaction(function () use ($invitationId): array {
            $invitation = MunicipalAdministratorInvitation::query()
                ->with(['onboardingRun', 'user'])
                ->whereKey($invitationId)
                ->lockForUpdate()
                ->firstOrFail();
            $run = MunicipalityOnboardingRun::query()
                ->whereKey($invitation->onboarding_run_id)
                ->lockForUpdate()
                ->firstOrFail();
            $user = User::query()
                ->whereKey($invitation->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($run->status !== MunicipalityOnboardingStatus::Completed) {
                throw new DomainException('O convite depende de um onboarding concluído.');
            }

            if ($user->status !== 'active'
                || $user->municipality_id === null
                || ! $user->mfa_required) {
                throw new DomainException('A conta administrativa municipal deixou de ser elegível.');
            }

            if ($invitation->status === MunicipalAdministratorInvitationStatus::Consumed) {
                return ['skip' => true];
            }

            if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
                $invitation->forceFill([
                    'status' => MunicipalAdministratorInvitationStatus::Expired,
                ])->save();

                return ['skip' => true];
            }

            if ($invitation->status === MunicipalAdministratorInvitationStatus::Sent) {
                return ['skip' => true];
            }

            $invitation->forceFill([
                'status' => MunicipalAdministratorInvitationStatus::Queued,
                'attempt_count' => $invitation->attempt_count + 1,
                'queued_at' => $invitation->queued_at ?? now(),
                'failed_at' => null,
                'last_failure_code' => null,
            ])->save();

            return [
                'skip' => false,
                'invitation_id' => (int) $invitation->id,
                'user_id' => (int) $user->id,
                'run_id' => (int) $run->id,
            ];
        });

        if ($context['skip']) {
            return;
        }

        $user = User::query()->findOrFail((int) $context['user_id']);
        $expiresInMinutes = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60,
        );
        $broker = Password::broker();

        if (! $broker instanceof ConcretePasswordBroker) {
            throw new DomainException('O broker de palavras-passe configurado não suporta criação de tokens.');
        }

        $token = $broker->createToken($user);

        $user->notify(new MunicipalAdministratorInvitationNotification(
            token: $token,
            expiresInMinutes: $expiresInMinutes,
        ));

        DB::transaction(function () use ($context, $expiresInMinutes): void {
            $invitation = MunicipalAdministratorInvitation::query()
                ->whereKey((int) $context['invitation_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($invitation->status === MunicipalAdministratorInvitationStatus::Consumed) {
                return;
            }

            $invitation->forceFill([
                'status' => MunicipalAdministratorInvitationStatus::Sent,
                'sent_at' => now(),
                'failed_at' => null,
                'expires_at' => now()->addMinutes($expiresInMinutes),
                'last_failure_code' => null,
            ])->save();

            $run = MunicipalityOnboardingRun::query()->find((int) $context['run_id']);
            $user = User::query()->find($invitation->user_id);

            $this->audit->record(
                eventCode: 'municipal_administrator_invitation_sent',
                auditable: $invitation,
                category: AuditEventCategory::Security,
                severity: AuditEventSeverity::Info,
                description: 'Convite de configuração de palavra-passe submetido ao transport.',
                metadata: [
                    'onboarding_run_id' => $run?->id,
                    'invitation_id' => $invitation->id,
                    'administrator_user_id' => $user?->id,
                    'attempt_count' => $invitation->attempt_count,
                    'status' => $invitation->status->value,
                ],
                subject: $user,
                useAuthenticatedUser: false,
            );
        });
    }

    public function markFailed(int $invitationId, Throwable $exception): void
    {
        DB::transaction(function () use ($invitationId, $exception): void {
            $invitation = MunicipalAdministratorInvitation::query()
                ->whereKey($invitationId)
                ->lockForUpdate()
                ->first();

            if (! $invitation instanceof MunicipalAdministratorInvitation
                || in_array($invitation->status, [
                    MunicipalAdministratorInvitationStatus::Consumed,
                    MunicipalAdministratorInvitationStatus::Expired,
                    MunicipalAdministratorInvitationStatus::Sent,
                ], true)) {
                return;
            }

            $failureCode = class_basename($exception);
            $invitation->forceFill([
                'status' => MunicipalAdministratorInvitationStatus::Failed,
                'failed_at' => now(),
                'last_failure_code' => mb_substr($failureCode, 0, 120),
            ])->save();

            $user = User::query()->find($invitation->user_id);

            $this->audit->record(
                eventCode: 'municipal_administrator_invitation_failed',
                auditable: $invitation,
                category: AuditEventCategory::Security,
                severity: AuditEventSeverity::Warning,
                description: 'Falhou a entrega do convite administrativo municipal.',
                metadata: [
                    'onboarding_run_id' => $invitation->onboarding_run_id,
                    'invitation_id' => $invitation->id,
                    'administrator_user_id' => $invitation->user_id,
                    'attempt_count' => $invitation->attempt_count,
                    'failure_code' => $invitation->last_failure_code,
                ],
                subject: $user,
                useAuthenticatedUser: false,
            );
        });
    }

    public function markConsumed(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $invitation = MunicipalAdministratorInvitation::query()
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    MunicipalAdministratorInvitationStatus::Pending,
                    MunicipalAdministratorInvitationStatus::Queued,
                    MunicipalAdministratorInvitationStatus::Sent,
                    MunicipalAdministratorInvitationStatus::Failed,
                ])
                ->lockForUpdate()
                ->first();

            if (! $invitation instanceof MunicipalAdministratorInvitation) {
                return;
            }

            $invitation->forceFill([
                'status' => MunicipalAdministratorInvitationStatus::Consumed,
                'consumed_at' => now(),
                'last_failure_code' => null,
            ])->save();

            $this->audit->record(
                eventCode: 'municipal_administrator_invitation_consumed',
                auditable: $invitation,
                category: AuditEventCategory::Security,
                severity: AuditEventSeverity::Notice,
                description: 'O administrador municipal definiu a palavra-passe inicial.',
                metadata: [
                    'onboarding_run_id' => $invitation->onboarding_run_id,
                    'invitation_id' => $invitation->id,
                    'administrator_user_id' => $user->id,
                    'status' => $invitation->status->value,
                ],
                subject: $user,
                actor: $user,
                useAuthenticatedUser: false,
            );
        });
    }
}
