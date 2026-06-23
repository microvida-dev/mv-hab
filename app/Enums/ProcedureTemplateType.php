<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProcedureTemplateType: string
{
    use HasOptions;

    case ApplicationReport = 'application_report';
    case DocumentDossier = 'document_dossier';
    case ProvisionalList = 'provisional_list';
    case DefinitiveList = 'definitive_list';
    case ProcedureMinute = 'procedure_minute';
    case Notification = 'notification';
    case ProcessConfirmation = 'process_confirmation';
    case InternalNote = 'internal_note';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationReport => 'Relatório de candidatura',
            self::DocumentDossier => 'Dossier documental',
            self::ProvisionalList => 'Lista provisória',
            self::DefinitiveList => 'Lista definitiva',
            self::ProcedureMinute => 'Ata do procedimento',
            self::Notification => 'Notificação',
            self::ProcessConfirmation => 'Confirmação de processo',
            self::InternalNote => 'Nota interna',
        };
    }
}
