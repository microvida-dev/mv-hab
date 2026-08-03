<?php

namespace App\Enums;

enum ApplicationResultChangeType: string
{
    case Added = 'added';
    case Removed = 'removed';
    case Changed = 'changed';
    case Unchanged = 'unchanged';
}
