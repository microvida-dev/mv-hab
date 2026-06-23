<?php

namespace App\Services\Security;

use App\Enums\AccessLogType;
use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AccessLogService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        AccessLogType|string $type,
        ?User $user = null,
        ?Model $resource = null,
        ?int $statusCode = null,
        array $metadata = [],
    ): AccessLog {
        $request = $this->request();
        $type = $type instanceof AccessLogType ? $type : AccessLogType::from($type);

        return AccessLog::query()->create([
            'user_id' => $user?->id,
            'access_type' => $type,
            'resource_type' => $resource?->getMorphClass(),
            'resource_id' => $resource?->getKey(),
            'route_name' => $request?->route()?->getName(),
            'request_path' => $request ? '/'.$request->path() : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'session_id_hash' => $request?->hasSession() ? hash('sha256', (string) $request->session()->getId()) : null,
            'status_code' => $statusCode,
            'accessed_at' => now(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        return app(Request::class);
    }
}
