<?php

namespace App\Policies;

use App\Models\DocumentAiAnalysis;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentAiAnalysisPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'view')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }

    public function view(User $user, DocumentAiAnalysis $analysis): bool
    {
        return $this->viewAny($user);
    }

    public function viewSensitiveOutput(User $user, DocumentAiAnalysis $analysis): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'audit')
                || $user->hasPermission('audit_logs.view')
                || $user->hasPermission('*')
            );
    }

    public function viewExtractedFields(User $user, DocumentAiAnalysis $analysis): bool
    {
        return $this->view($user, $analysis);
    }

    public function viewSensitiveExtractedFields(User $user, DocumentAiAnalysis $analysis): bool
    {
        return $this->viewSensitiveOutput($user, $analysis);
    }

    public function viewHealthExtractedFields(User $user, DocumentAiAnalysis $analysis): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'audit')
                || $user->hasPermission('audit_logs.view')
                || $user->hasPermission('privacy.view')
                || $user->hasPermission('*')
            );
    }

    public function markFieldForReview(User $user, DocumentAiAnalysis $analysis): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'approve')
                || $this->canAccess($user, self::MODULE, 'update')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }

    public function markManualReview(User $user, DocumentAiAnalysis $analysis): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'approve')
                || $this->canAccess($user, self::MODULE, 'update')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }
}
