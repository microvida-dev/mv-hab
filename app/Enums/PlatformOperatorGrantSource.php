<?php

namespace App\Enums;

enum PlatformOperatorGrantSource: string
{
    case Bootstrap = 'bootstrap';

    case PlatformOperator = 'platform_operator';
}
