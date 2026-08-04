<?php

namespace App\Enums;

enum MunicipalityOnboardingStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
