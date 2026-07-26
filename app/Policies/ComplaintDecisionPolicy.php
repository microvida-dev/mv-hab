<?php

namespace App\Policies;

use App\Enums\ComplaintDecisionStatus;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ComplaintDecisionPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'complaints', 'view');
    }

    public function view(User $user, ComplaintDecision $decision): bool
    {
        $complaint = $decision->complaint;

        return $user->hasRole('candidate')
            ? $complaint instanceof Complaint && $complaint->user_id === $user->id && $decision->candidate_visible && $this->canAccess($user, 'complaints', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }

    public function approve(User $user, ComplaintDecision $decision): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->status($decision) !== ComplaintDecisionStatus::Approved
            && $this->canAccess($user, 'complaints', 'approve');
    }

    public function viewBackoffice(User $user, ComplaintDecision $decision): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'complaints', 'view')
            && $this->municipalScope->ownsComplaintDecision($user, $decision);
    }

    public function decideBackoffice(User $user, Complaint $complaint): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'complaints', 'decide')
            && $this->municipalScope->ownsComplaint($user, $complaint);
    }

    public function approveBackoffice(User $user, ComplaintDecision $decision): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->status($decision) !== ComplaintDecisionStatus::Approved
            && $this->canAccess($user, 'complaints', 'approve')
            && $this->municipalScope->ownsComplaintDecision($user, $decision);
    }

    public function cancelBackoffice(User $user, ComplaintDecision $decision): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'complaints', 'cancel')
            && $this->municipalScope->ownsComplaintDecision($user, $decision);
    }

    private function status(ComplaintDecision $decision): ?ComplaintDecisionStatus
    {
        $status = $decision->getAttribute('status');

        if ($status instanceof ComplaintDecisionStatus) {
            return $status;
        }

        return is_string($status) ? ComplaintDecisionStatus::tryFrom($status) : null;
    }
}
