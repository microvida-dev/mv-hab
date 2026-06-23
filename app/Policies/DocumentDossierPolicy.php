<?php

namespace App\Policies;

use App\Models\DocumentDossier;
use App\Models\User;

class DocumentDossierPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermissionTo('documents', 'view');
    }

    public function view(User $user, DocumentDossier $dossier): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole('candidate') && ($user->hasPermissionTo('documents', 'create') || $user->hasPermissionTo('documents', 'export'));
    }

    public function download(User $user, DocumentDossier $dossier): bool
    {
        return $this->view($user, $dossier);
    }
}
