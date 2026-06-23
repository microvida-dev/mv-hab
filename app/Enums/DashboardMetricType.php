<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DashboardMetricType: string
{
    use HasOptions;

    case Applications = 'applications';
    case Documents = 'documents';
    case Visits = 'visits';
    case Tickets = 'tickets';
    case Deadlines = 'deadlines';
    case Alerts = 'alerts';
    case Lists = 'lists';
    case Reports = 'reports';
    case Minutes = 'minutes';
    case Notifications = 'notifications';

    public function label(): string
    {
        return match ($this) {
            self::Applications => 'Candidaturas',
            self::Documents => 'Documentos',
            self::Visits => 'Visitas',
            self::Tickets => 'Tickets',
            self::Deadlines => 'Prazos',
            self::Alerts => 'Alertas',
            self::Lists => 'Listas',
            self::Reports => 'Relatórios',
            self::Minutes => 'Atas',
            self::Notifications => 'Notificações',
        };
    }
}
