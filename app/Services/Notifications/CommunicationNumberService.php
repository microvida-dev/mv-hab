<?php

namespace App\Services\Notifications;

use Illuminate\Support\Str;

class CommunicationNumberService
{
    public function notification(): string
    {
        return 'NOT-'.Str::upper((string) Str::ulid());
    }

    public function communication(): string
    {
        return 'COM-'.Str::upper((string) Str::ulid());
    }

    public function receipt(): string
    {
        return 'REC-'.Str::upper((string) Str::ulid());
    }

    public function document(): string
    {
        return 'DOC-'.Str::upper((string) Str::ulid());
    }
}
