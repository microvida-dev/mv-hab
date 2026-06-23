<?php

namespace App\Services\Notifications\Channels;

use App\Models\CommunicationDelivery;
use App\Models\User;

class InternalChannelService extends InAppChannelService
{
    public function send(CommunicationDelivery $delivery, ?User $actor = null): CommunicationDelivery
    {
        return parent::send($delivery, $actor);
    }
}
