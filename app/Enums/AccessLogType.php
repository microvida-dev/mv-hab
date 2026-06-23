<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AccessLogType: string
{
    use HasOptions;

    case Login = 'login';
    case Logout = 'logout';
    case FailedLogin = 'failed_login';
    case PageView = 'page_view';
    case RecordView = 'record_view';
    case DocumentView = 'document_view';
    case DocumentDownload = 'document_download';
    case ExportDownload = 'export_download';
    case ApiAccess = 'api_access';
    case AdminAccess = 'admin_access';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Login',
            self::Logout => 'Logout',
            self::FailedLogin => 'Falha de login',
            self::PageView => 'Vista de página',
            self::RecordView => 'Consulta de registo',
            self::DocumentView => 'Consulta documental',
            self::DocumentDownload => 'Download documental',
            self::ExportDownload => 'Download de exportação',
            self::ApiAccess => 'Acesso API',
            self::AdminAccess => 'Acesso backoffice',
        };
    }
}
