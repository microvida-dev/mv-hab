<?php

namespace App\Support;

class AuditEvents
{
    public const ACCESS = 'access';

    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    public const APPROVE = 'approve';

    public const REJECT = 'reject';

    public const PUBLISH = 'publish';

    public const EXPORT = 'export';

    public const DECISION = 'decision';
}
