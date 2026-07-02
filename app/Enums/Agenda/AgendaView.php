<?php

namespace App\Enums\Agenda;

enum AgendaView: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
}
