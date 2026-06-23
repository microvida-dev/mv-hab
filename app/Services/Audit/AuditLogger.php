<?php

namespace App\Services\Audit;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $event,
        ?Model $auditable = null,
        ?string $module = null,
        ?string $action = null,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
    ): AuditLog {
        $request = $this->request();

        $log = AuditLog::query()->create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'occurred_at' => now(),
        ]);

        if (Schema::hasTable('audit_events')) {
            app(AuditTrailService::class)->record(
                $event,
                $auditable,
                $this->categoryFor($module),
                AuditEventSeverity::Info,
                $description,
                $oldValues,
                $newValues,
                $metadata,
            );
        }

        return $log;
    }

    private function categoryFor(?string $module): AuditEventCategory
    {
        return match ($module) {
            'documents' => AuditEventCategory::Documents,
            'applications' => AuditEventCategory::Application,
            'reports' => AuditEventCategory::Reports,
            'eligibility' => AuditEventCategory::Workflow,
            'scoring' => AuditEventCategory::Scoring,
            'allocations' => AuditEventCategory::Allocation,
            'contracts' => AuditEventCategory::Contracts,
            'finance', 'payments' => AuditEventCategory::Finance,
            'maintenance', 'maintenance_requests', 'inspections' => AuditEventCategory::Maintenance,
            'notifications', 'communications' => AuditEventCategory::Communications,
            default => AuditEventCategory::System,
        };
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        return app(Request::class);
    }
}
