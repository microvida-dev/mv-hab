<?php

namespace App\Exceptions\Allocation;

use RuntimeException;

final class LotteryAlreadyExecutedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Já existe um sorteio executado para esta afetação.'
        );
    }
}
