<?php

namespace App\Services\Dashboard\Timeline;

use App\Data\Dashboard\TimelineEvent;
use App\Models\User;

interface TimelineProviderInterface
{
    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<int, TimelineEvent>
     */
    public function forUser(User $user, array $dashboard = []): array;
}
