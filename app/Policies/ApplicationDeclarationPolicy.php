<?php

namespace App\Policies;

use App\Models\ApplicationDeclaration;
use App\Models\User;

class ApplicationDeclarationPolicy
{
    public function view(User $user, ApplicationDeclaration $declaration): bool
    {
        return $user->can('view', $declaration->application);
    }
}
