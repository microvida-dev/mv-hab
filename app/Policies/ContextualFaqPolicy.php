<?php

namespace App\Policies;

use App\Models\ContextualFaq;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ContextualFaqPolicy
{
    use ChecksPermissions;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'contextual_faqs', 'view');
    }

    public function view(User $user, ContextualFaq $faq): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'contextual_faqs', 'create');
    }

    public function update(User $user, ContextualFaq $faq): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'contextual_faqs', 'update');
    }

    public function delete(User $user, ContextualFaq $faq): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'contextual_faqs', 'delete');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contextual_faqs', 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contextual_faqs', 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, ContextualFaq $faq): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsContextualFaq($user, $faq);
    }

    public function updateBackoffice(User $user, ContextualFaq $faq): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contextual_faqs', 'update')
            && $this->municipalScope->canMutateContextualFaq($user, $faq);
    }

    public function deleteBackoffice(User $user, ContextualFaq $faq): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'contextual_faqs', 'delete')
            && $this->municipalScope->canMutateContextualFaq($user, $faq);
    }
}
