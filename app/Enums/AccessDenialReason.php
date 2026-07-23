<?php

namespace App\Enums;

enum AccessDenialReason: string
{
    case MissingPermission = 'missing_permission';
    case FeatureUnavailable = 'feature_unavailable';
    case RecordOutOfScope = 'record_out_of_scope';
    case MfaRequired = 'mfa_required';
    case InactiveAccount = 'inactive_account';
    case CandidateBackofficeBoundary = 'candidate_backoffice_boundary';

    public function publicMessage(): string
    {
        return match ($this) {
            self::MissingPermission => 'Não tem permissão para realizar esta ação.',
            self::FeatureUnavailable => 'Esta funcionalidade não está disponível para o Município atual.',
            self::RecordOutOfScope => 'Este recurso não está disponível no seu âmbito de acesso.',
            self::MfaRequired => 'Confirme a autenticação multifator para continuar.',
            self::InactiveAccount => 'A sua conta não está autorizada a aceder ao backoffice.',
            self::CandidateBackofficeBoundary => 'Esta área está reservada à operação municipal.',
        };
    }

    public function publicCode(): string
    {
        return 'access_denied';
    }

    public function shouldAudit(): bool
    {
        return match ($this) {
            self::RecordOutOfScope,
            self::InactiveAccount,
            self::CandidateBackofficeBoundary => true,
            self::MissingPermission,
            self::FeatureUnavailable,
            self::MfaRequired => false,
        };
    }
}
