<?php

namespace App\Policies;

use App\Models\AdditionalDocumentSubmission;
use App\Models\Application;
use App\Models\User;

class AdditionalDocumentSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('documents', 'view') || $user->hasPermissionTo('applications', 'view');
    }

    public function view(User $user, AdditionalDocumentSubmission $submission): bool
    {
        return $submission->user_id === $user->id || $user->hasPermissionTo('documents', 'view');
    }

    public function create(User $user, Application $application): bool
    {
        return $application->user_id === $user->id && $application->status->isActive();
    }

    public function update(User $user, AdditionalDocumentSubmission $submission): bool
    {
        return $user->hasPermissionTo('documents', 'approve') || $user->hasPermissionTo('documents', 'reject');
    }
}
