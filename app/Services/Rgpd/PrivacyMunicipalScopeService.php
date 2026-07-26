<?php

namespace App\Services\Rgpd;

use App\Models\AnonymizationRequest;
use App\Models\ConsentPurpose;
use App\Models\DataExportPackage;
use App\Models\DataSubjectRequest;
use App\Models\RetentionExecution;
use App\Models\RetentionPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class PrivacyMunicipalScopeService
{
    /**
     * Global purposes are catalogue defaults and remain read-only. Municipal
     * purposes are visible and mutable only in their owning municipality.
     *
     * @param  Builder<ConsentPurpose>  $query
     * @return Builder<ConsentPurpose>
     */
    public function purposes(Builder $query, User $actor): Builder
    {
        return $this->catalogueAndMunicipal($query, $actor);
    }

    public function ownsMutablePurpose(User $actor, ConsentPurpose $purpose): bool
    {
        return $this->ownsMunicipalRecord($actor, $purpose);
    }

    /**
     * @param  Builder<RetentionPolicy>  $query
     * @return Builder<RetentionPolicy>
     */
    public function retentionPolicies(Builder $query, User $actor): Builder
    {
        return $this->catalogueAndMunicipal($query, $actor);
    }

    public function canUseRetentionPolicy(User $actor, RetentionPolicy $policy): bool
    {
        return $this->retentionPolicies(
            RetentionPolicy::query()->whereKey($policy),
            $actor,
        )->exists();
    }

    public function ownsMutableRetentionPolicy(User $actor, RetentionPolicy $policy): bool
    {
        return $this->ownsMunicipalRecord($actor, $policy);
    }

    /**
     * @param  Builder<DataSubjectRequest>  $query
     * @return Builder<DataSubjectRequest>
     */
    public function requests(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsRequest(User $actor, DataSubjectRequest $request): bool
    {
        return $this->requests(
            DataSubjectRequest::query()->whereKey($request),
            $actor,
        )->exists();
    }

    /**
     * @param  Builder<DataExportPackage>  $query
     * @return Builder<DataExportPackage>
     */
    public function exports(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsExport(User $actor, DataExportPackage $package): bool
    {
        return $this->exports(
            DataExportPackage::query()->whereKey($package),
            $actor,
        )->exists();
    }

    /**
     * @param  Builder<RetentionExecution>  $query
     * @return Builder<RetentionExecution>
     */
    public function retentionExecutions(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsRetentionExecution(User $actor, RetentionExecution $execution): bool
    {
        return $this->retentionExecutions(
            RetentionExecution::query()->whereKey($execution),
            $actor,
        )->exists();
    }

    /**
     * @param  Builder<AnonymizationRequest>  $query
     * @return Builder<AnonymizationRequest>
     */
    public function anonymizationRequests(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsAnonymizationRequest(User $actor, AnonymizationRequest $request): bool
    {
        return $this->anonymizationRequests(
            AnonymizationRequest::query()->whereKey($request),
            $actor,
        )->exists();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function users(Builder $query, User $actor): Builder
    {
        return $this->forMunicipality($query, $actor);
    }

    public function ownsUser(User $actor, User $subject): bool
    {
        return $actor->municipality_id !== null
            && (int) $subject->municipality_id === (int) $actor->municipality_id;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function catalogueAndMunicipal(Builder $query, User $actor): Builder
    {
        if ($actor->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $records) use ($actor): void {
            $records
                ->whereNull('municipality_id')
                ->orWhere('municipality_id', $actor->municipality_id);
        });
    }

    private function ownsMunicipalRecord(User $actor, Model $record): bool
    {
        return $actor->municipality_id !== null
            && (int) $record->getAttribute('municipality_id') === (int) $actor->municipality_id;
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
