<?php

namespace App\Policies;

use App\Models\DocumentReview;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DocumentReviewPolicy
{
    use ChecksPermissions;

    private const MODULE = 'documents';

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, DocumentReview $documentReview): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }
}
