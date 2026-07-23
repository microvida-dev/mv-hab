<?php

namespace App\Enums;

enum PlatformOperatorStatus: string
{
    case Active = 'active';

    case Revoked = 'revoked';
}
