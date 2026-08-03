<?php

namespace App\Enums;

enum Program53FailureDisposition: string
{
    case Retryable = 'retryable';
    case Terminal = 'terminal';
    case RequiresNewOperation = 'requires_new_operation';
}
