<?php

namespace App\Services\Security;

use App\Models\AccessLog;
use App\Models\AuditEvent;
use App\Models\BackupReview;
use App\Models\PermissionReview;
use App\Models\SecurityAlert;
use App\Models\SecurityAlertRule;
use App\Models\SecurityChecklist;
use App\Models\SecurityChecklistItem;
use App\Models\SensitiveDataAccessLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SecurityMunicipalScopeService
{
    /**
     * @param  Builder<AuditEvent>  $query
     * @return Builder<AuditEvent>
     */
    public function auditEvents(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsAuditEvent(User $actor, AuditEvent $event): bool
    {
        return $this->auditEvents(AuditEvent::query()->whereKey($event), $actor)->exists();
    }

    /**
     * @param  Builder<AccessLog>  $query
     * @return Builder<AccessLog>
     */
    public function accessLogs(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    /**
     * @param  Builder<SensitiveDataAccessLog>  $query
     * @return Builder<SensitiveDataAccessLog>
     */
    public function sensitiveAccessLogs(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    /**
     * @param  Builder<PermissionReview>  $query
     * @return Builder<PermissionReview>
     */
    public function permissionReviews(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsPermissionReview(User $actor, PermissionReview $review): bool
    {
        return $this->permissionReviews(
            PermissionReview::query()->whereKey($review),
            $actor,
        )->exists();
    }

    /**
     * Global rules are catalogue defaults and remain read-only. Municipal
     * rules are visible and mutable only in their owning municipality.
     *
     * @param  Builder<SecurityAlertRule>  $query
     * @return Builder<SecurityAlertRule>
     */
    public function alertRules(Builder $query, User $actor): Builder
    {
        if ($actor->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $rules) use ($actor): void {
            $rules
                ->whereNull('municipality_id')
                ->orWhere('municipality_id', $actor->municipality_id);
        });
    }

    public function ownsMutableAlertRule(User $actor, SecurityAlertRule $rule): bool
    {
        return $actor->municipality_id !== null
            && (int) $rule->municipality_id === (int) $actor->municipality_id;
    }

    /**
     * @param  Builder<SecurityAlert>  $query
     * @return Builder<SecurityAlert>
     */
    public function alerts(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsAlert(User $actor, SecurityAlert $alert): bool
    {
        return $this->alerts(SecurityAlert::query()->whereKey($alert), $actor)->exists();
    }

    /**
     * @param  Builder<BackupReview>  $query
     * @return Builder<BackupReview>
     */
    public function backupReviews(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    /**
     * @param  Builder<SecurityChecklist>  $query
     * @return Builder<SecurityChecklist>
     */
    public function checklists(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsChecklist(User $actor, SecurityChecklist $checklist): bool
    {
        return $this->checklists(
            SecurityChecklist::query()->whereKey($checklist),
            $actor,
        )->exists();
    }

    public function ownsChecklistItem(User $actor, SecurityChecklistItem $item): bool
    {
        if ($actor->municipality_id === null) {
            return false;
        }

        return SecurityChecklistItem::query()
            ->whereKey($item)
            ->whereHas(
                'checklist',
                fn ($checklist) => $checklist->where('municipality_id', $actor->municipality_id),
            )
            ->exists();
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function forMunicipality(Builder $query, User $actor): Builder
    {
        if ($actor->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('municipality_id', $actor->municipality_id);
    }
}
