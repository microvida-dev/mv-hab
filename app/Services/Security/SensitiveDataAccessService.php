<?php

namespace App\Services\Security;

use App\Models\SensitiveDataAccessLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SensitiveDataAccessService
{
    public function __construct(private readonly SecurityAlertService $alerts) {}

    public function record(
        User $user,
        Model $resource,
        string $action,
        ?User $subject = null,
        string $sensitivity = 'personal',
        ?string $reason = null,
    ): SensitiveDataAccessLog {
        $request = $this->request();

        $log = SensitiveDataAccessLog::query()->create([
            'user_id' => $user->id,
            'subject_user_id' => $subject?->id,
            'resource_type' => $resource->getMorphClass(),
            'resource_id' => $resource->getKey(),
            'sensitivity_level' => $sensitivity,
            'access_reason' => $reason,
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'accessed_at' => now(),
        ]);

        $this->alerts->evaluateSensitiveAccess($log);

        return $log;
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        return app(Request::class);
    }
}
