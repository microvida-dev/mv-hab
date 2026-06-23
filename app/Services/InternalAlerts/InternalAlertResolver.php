<?php

namespace App\Services\InternalAlerts;

use App\Models\InternalAlert;
use App\Models\User;

class InternalAlertResolver
{
    public function __construct(private readonly InternalAlertService $alerts) {}

    public function resolve(InternalAlert $alert, User $actor): InternalAlert
    {
        return $this->alerts->resolve($alert, $actor);
    }

    public function dismiss(InternalAlert $alert, User $actor): InternalAlert
    {
        return $this->alerts->dismiss($alert, $actor);
    }
}
