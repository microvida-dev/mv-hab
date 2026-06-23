<?php

namespace App\Services\Rgpd;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\ConsentStatus;
use App\Models\ConsentPurpose;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\Audit\AuditTrailService;
use Illuminate\Http\Request;
use RuntimeException;

class UserConsentService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function grant(User $user, ConsentPurpose $purpose, string $snapshot, string $source = 'web'): UserConsent
    {
        $request = $this->request();

        $consent = UserConsent::query()->create([
            'user_id' => $user->id,
            'consent_purpose_id' => $purpose->id,
            'status' => ConsentStatus::Active,
            'consented_at' => now(),
            'source' => $source,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'text_snapshot' => $snapshot,
            'version' => 1,
        ]);

        $this->audit->record('user_consent.granted', $consent, AuditEventCategory::Rgpd, AuditEventSeverity::Notice, 'Consentimento registado.', subject: $user, actor: $user);

        return $consent;
    }

    public function withdraw(UserConsent $consent, User $actor): UserConsent
    {
        $consent->loadMissing('purpose');
        $purpose = $consent->purpose;
        if (! $purpose instanceof ConsentPurpose) {
            throw new RuntimeException('Consentimento sem finalidade RGPD associada.');
        }

        if ($purpose->is_required || ! $purpose->requires_explicit_consent) {
            throw new RuntimeException('Esta finalidade assenta em base legal obrigatória e não pode ser revogada como consentimento opcional.');
        }

        $consent->forceFill([
            'status' => ConsentStatus::Withdrawn,
            'withdrawn_at' => now(),
        ])->save();

        $subject = $consent->user;
        if (! $subject instanceof User) {
            throw new RuntimeException('Consentimento sem titular associado.');
        }

        $this->audit->record('user_consent.withdrawn', $consent, AuditEventCategory::Rgpd, AuditEventSeverity::Notice, 'Consentimento opcional retirado.', subject: $subject, actor: $actor);

        return $consent->refresh();
    }

    private function request(): ?Request
    {
        return app()->bound('request') ? request() : null;
    }
}
