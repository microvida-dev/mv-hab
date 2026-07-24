<?php

namespace App\Policies;

use App\Models\CommunicationLog;
use App\Models\CommunicationReceipt;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class CommunicationReceiptPolicy
{
    use ChecksCommunicationAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, CommunicationReceipt $receipt): bool
    {
        $communication = $receipt->communication;

        return $user->hasRole('candidate')
            ? $communication instanceof CommunicationLog && $communication->recipient_user_id === $user->id
            : $this->canViewCommunications($user);
    }

    public function downloadBackoffice(User $user, CommunicationReceipt $receipt): bool
    {
        return ! $user->hasRole('candidate')
            && $user->hasPermissionTo('notifications', 'receipts.download')
            && $this->municipalScope->ownsCommunicationReceipt($user, $receipt);
    }
}
