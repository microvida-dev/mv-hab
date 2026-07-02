<?php

namespace App\Enums\Dashboard\Timeline;

enum TimelineWorkspace: string
{
    case Operations = 'operations';
    case Applications = 'applications';
    case Contests = 'contests';
    case Patrimony = 'patrimony';
    case Maintenance = 'maintenance';
    case Tenant = 'tenant';
    case Finance = 'finance';
    case Administration = 'administration';
}
