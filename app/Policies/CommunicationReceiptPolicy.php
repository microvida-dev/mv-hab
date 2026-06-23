<?php

namespace App\Policies;

use App\Models\CommunicationLog;
use App\Models\CommunicationReceipt;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;

class CommunicationReceiptPolicy
{
    use ChecksCommunicationAccess;

    public function view(User $user, CommunicationReceipt $receipt): bool
    {
        $communication = $receipt->communication;

        return $user->hasRole('candidate')
            ? $communication instanceof CommunicationLog && $communication->recipient_user_id === $user->id
            : $this->canViewCommunications($user);
    }
}
