<?php

namespace App\Policies;

use App\Models\LotteryDraw;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class LotteryDrawPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, LotteryDraw $draw): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function update(User $user, LotteryDraw $draw): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function approve(User $user, LotteryDraw $draw): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'approve');
    }

    public function audit(User $user, LotteryDraw $draw): bool
    {
        return $this->canAccess($user, 'allocations', 'audit') || $user->hasRole('auditor');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'lotteries', 'view');
    }

    public function viewBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsLotteryDraw($user, $draw);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->municipality_id !== null
            && $this->canAccess($user, 'lotteries', 'create');
    }

    public function updateBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'update');
    }

    public function runBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'run');
    }

    public function validateBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'validate');
    }

    public function cancelBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'cancel');
    }

    public function loadParticipantsBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'participants.load');
    }

    public function lockParticipantsBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'participants.lock');
    }

    public function generateConvocationsBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'convocations.generate');
    }

    public function registerAttendanceBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'attendance.manage');
    }

    public function generateReportBackoffice(User $user, LotteryDraw $draw): bool
    {
        return $this->canMutateBackoffice($user, $draw, 'reports.generate');
    }

    private function canMutateBackoffice(User $user, LotteryDraw $draw, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'lotteries', $action)
            && $this->municipalScope->ownsLotteryDraw($user, $draw);
    }
}
