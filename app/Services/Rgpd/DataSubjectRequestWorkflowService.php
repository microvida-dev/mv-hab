<?php

namespace App\Services\Rgpd;

use App\Models\DataSubjectRequest;

class DataSubjectRequestWorkflowService
{
    public function remainingDays(DataSubjectRequest $request): ?int
    {
        return $request->due_at ? (int) now()->diffInDays($request->due_at, false) : null;
    }
}
