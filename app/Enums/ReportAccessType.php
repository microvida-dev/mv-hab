<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReportAccessType: string
{
    use HasOptions;

    case ViewDashboard = 'view_dashboard';
    case ViewReport = 'view_report';
    case RunReport = 'run_report';
    case ExportReport = 'export_report';
    case DownloadExport = 'download_export';

    public function label(): string
    {
        return match ($this) {
            self::ViewDashboard => 'Consulta de dashboard',
            self::ViewReport => 'Consulta de relatório',
            self::RunReport => 'Execução de relatório',
            self::ExportReport => 'Exportação de relatório',
            self::DownloadExport => 'Download de exportação',
        };
    }
}
