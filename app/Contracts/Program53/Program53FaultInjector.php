<?php

namespace App\Contracts\Program53;

use App\Data\Program53\Program53OperationalContext;

interface Program53FaultInjector
{
    public function checkpoint(
        string $checkpoint,
        Program53OperationalContext $context,
    ): void;
}
