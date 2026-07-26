<?php

namespace App\Policies;

use App\Models\DocumentAiSuggestion;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DocumentAiSuggestionPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'view')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }

    public function view(User $user, DocumentAiSuggestion $suggestion): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, DocumentAiSuggestion $suggestion): bool
    {
        return $this->manage($user);
    }

    public function accept(User $user, DocumentAiSuggestion $suggestion): bool
    {
        return $this->manage($user);
    }

    public function dismiss(User $user, DocumentAiSuggestion $suggestion): bool
    {
        return $this->manage($user);
    }

    public function reviewBackoffice(User $user, DocumentAiSuggestion $suggestion): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'review_ai')
            && $this->municipalScope->ownsDocumentAiSuggestion($user, $suggestion);
    }

    private function manage(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'update')
                || $this->canAccess($user, self::MODULE, 'approve')
                || $this->canAccess($user, self::MODULE, 'audit')
            );
    }
}
