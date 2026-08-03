<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BulkApplicationReviewAction: string
{
    use HasOptions;

    case AssignAnalyst = 'assign_analyst';
    case MarkDocumentsUnderReview = 'mark_documents_under_review';
    case ValidateDocuments = 'validate_documents';
    case RejectDocuments = 'reject_documents';
    case MarkReadyForClosure = 'mark_ready_for_closure';
    case ReopenReview = 'reopen_review';

    public function label(): string
    {
        return match ($this) {
            self::AssignAnalyst => 'Atribuir analista',
            self::MarkDocumentsUnderReview => 'Colocar documentos em análise',
            self::ValidateDocuments => 'Validar documentos',
            self::RejectDocuments => 'Rejeitar documentos',
            self::MarkReadyForClosure => 'Marcar candidaturas prontas para fecho',
            self::ReopenReview => 'Reabrir análise',
        };
    }

    public function requiresAssignee(): bool
    {
        return $this === self::AssignAnalyst;
    }

    public function requiresDocuments(): bool
    {
        return in_array($this, [
            self::MarkDocumentsUnderReview,
            self::ValidateDocuments,
            self::RejectDocuments,
        ], true);
    }

    public function requiresReason(): bool
    {
        return in_array($this, [
            self::RejectDocuments,
            self::ReopenReview,
        ], true);
    }

    public function isDocumentAction(): bool
    {
        return $this->requiresDocuments();
    }
}
