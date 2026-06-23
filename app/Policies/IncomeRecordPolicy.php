<?php

namespace App\Policies;

use App\Enums\AdhesionRegistrationStatus;
use App\Models\AdhesionRegistration;
use App\Models\IncomeRecord;
use App\Models\User;

class IncomeRecordPolicy
{
    public function view(User $user, IncomeRecord $record): bool
    {
        return $this->owns($user, $record)
            && $user->hasPermissionTo('income_records', 'view');
    }

    public function update(User $user, IncomeRecord $record): bool
    {
        return $this->owns($user, $record)
            && $user->hasPermissionTo('income_records', 'update')
            && $this->registrationIsEditable($this->registration($record));
    }

    public function delete(User $user, IncomeRecord $record): bool
    {
        return $this->owns($user, $record)
            && $user->hasPermissionTo('income_records', 'delete')
            && $this->registrationIsEditable($this->registration($record));
    }

    private function owns(User $user, IncomeRecord $record): bool
    {
        $registration = $record->adhesionRegistration;

        return $user->hasRole('candidate')
            && $registration instanceof AdhesionRegistration
            && $registration->user_id === $user->id;
    }

    private function registration(IncomeRecord $record): ?AdhesionRegistration
    {
        $registration = $record->adhesionRegistration;

        return $registration instanceof AdhesionRegistration ? $registration : null;
    }

    private function registrationIsEditable(?AdhesionRegistration $registration): bool
    {
        $status = $registration?->getAttribute('status');

        if (is_string($status)) {
            $status = AdhesionRegistrationStatus::tryFrom($status);
        }

        return in_array($status, [AdhesionRegistrationStatus::Incomplete, AdhesionRegistrationStatus::Registered], true);
    }
}
