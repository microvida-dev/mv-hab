<?php

namespace App\Policies;

use App\Models\GeneratedProcedureDocument;
use App\Models\User;

class GeneratedProcedureDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermissionTo('documents', 'view');
    }

    public function view(User $user, GeneratedProcedureDocument $document): bool
    {
        return $this->viewAny($user);
    }

    public function download(User $user, GeneratedProcedureDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function approve(User $user, GeneratedProcedureDocument $document): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('documents', 'approve');
    }
}
