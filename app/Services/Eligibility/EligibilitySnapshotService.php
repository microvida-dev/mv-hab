<?php

namespace App\Services\Eligibility;

use App\Models\EligibilityCheck;

class EligibilitySnapshotService
{
    /**
     * @param  array<string, array<string, mixed>>  $snapshots
     */
    public function store(EligibilityCheck $check, array $snapshots): void
    {
        foreach ($snapshots as $type => $data) {
            $check->snapshots()->create([
                'snapshot_type' => $type,
                'data' => $data,
            ]);
        }
    }
}
