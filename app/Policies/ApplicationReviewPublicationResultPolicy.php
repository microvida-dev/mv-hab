<?php

namespace App\Policies;

use App\Models\ApplicationReviewPublicationResult;
use App\Models\User;

class ApplicationReviewPublicationResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate');
    }

    public function view(
        User $user,
        ApplicationReviewPublicationResult $result,
    ): bool {
        return $this->viewAny($user)
            && (int) $result->user_id === (int) $user->id
            && $result->published_at->lessThanOrEqualTo(now());
    }
}
