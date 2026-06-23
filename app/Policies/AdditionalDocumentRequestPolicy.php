<?php

namespace App\Policies;

use App\Models\AdditionalDocumentRequest;
use App\Models\User;

class AdditionalDocumentRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('documents', 'view') || $user->hasPermissionTo('applications', 'view');
    }

    public function view(User $user, AdditionalDocumentRequest $request): bool
    {
        return $request->user_id === $user->id || $user->hasPermissionTo('documents', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('documents', 'create') || $user->hasPermissionTo('applications', 'update');
    }

    public function update(User $user, AdditionalDocumentRequest $request): bool
    {
        return $user->hasPermissionTo('documents', 'update') || $user->hasPermissionTo('applications', 'update');
    }
}
