<?php

namespace Tests\Unit\Security;

use App\Enums\AccessDenialReason;
use PHPUnit\Framework\TestCase;

class AccessDenialReasonTest extends TestCase
{
    public function test_public_messages_are_stable_and_written_in_portuguese(): void
    {
        $this->assertSame(
            'Não tem permissão para realizar esta ação.',
            AccessDenialReason::MissingPermission->publicMessage(),
        );
        $this->assertSame(
            'Esta funcionalidade não está disponível para o Município atual.',
            AccessDenialReason::FeatureUnavailable->publicMessage(),
        );
        $this->assertSame(
            'Este recurso não está disponível no seu âmbito de acesso.',
            AccessDenialReason::RecordOutOfScope->publicMessage(),
        );
        $this->assertSame(
            'Confirme a autenticação multifator para continuar.',
            AccessDenialReason::MfaRequired->publicMessage(),
        );
        $this->assertSame(
            'A sua conta não está autorizada a aceder ao backoffice.',
            AccessDenialReason::InactiveAccount->publicMessage(),
        );
        $this->assertSame(
            'Esta área está reservada à operação municipal.',
            AccessDenialReason::CandidateBackofficeBoundary->publicMessage(),
        );
    }

    public function test_all_reasons_expose_only_the_generic_public_code(): void
    {
        foreach (AccessDenialReason::cases() as $reason) {
            $this->assertSame('access_denied', $reason->publicCode());
        }
    }

    public function test_only_security_relevant_safe_requests_are_audited_by_default(): void
    {
        $this->assertTrue(AccessDenialReason::RecordOutOfScope->shouldAudit());
        $this->assertTrue(AccessDenialReason::InactiveAccount->shouldAudit());
        $this->assertTrue(AccessDenialReason::CandidateBackofficeBoundary->shouldAudit());

        $this->assertFalse(AccessDenialReason::MissingPermission->shouldAudit());
        $this->assertFalse(AccessDenialReason::FeatureUnavailable->shouldAudit());
        $this->assertFalse(AccessDenialReason::MfaRequired->shouldAudit());
    }
}
