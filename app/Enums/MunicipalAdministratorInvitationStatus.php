<?php

namespace App\Enums;

enum MunicipalAdministratorInvitationStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Consumed = 'consumed';
    case Expired = 'expired';
}
