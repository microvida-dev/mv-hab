<?php

namespace App\Policies;

use App\Models\ApplicationPreference;
use App\Models\User;

class ApplicationPreferencePolicy
{
    public function view(User $user, ApplicationPreference $preference): bool
    {
        return $user->can('view', $preference->application);
    }

    public function update(User $user, ApplicationPreference $preference): bool
    {
        return $user->can('update', $preference->application);
    }
}
