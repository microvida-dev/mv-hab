<?php

namespace App\Services\Dashboard\Timeline;

use App\Models\User;

interface TimelineProviderInterface
{
    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<int, \App\Data\Dashboard\TimelineEvent>
     */
    public function forUser(User $user, array $dashboard = []): array;
}
