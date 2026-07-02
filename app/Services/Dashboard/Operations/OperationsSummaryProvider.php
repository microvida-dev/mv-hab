<?php

namespace App\Services\Dashboard\Operations;

use App\Models\User;

class OperationsSummaryProvider
{
    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function forUser(User $user, array $dashboard): array
    {
        return [
            'metrics' => collect($dashboard['metrics'] ?? [])->values()->all(),
        ];
    }
}
