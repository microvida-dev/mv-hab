<?php

namespace App\Policies;

use App\Models\ContextualFaq;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ContextualFaqPolicy
{
    use ChecksPermissions;

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
}
