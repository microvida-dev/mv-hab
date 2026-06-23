<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProcessActionType: string
{
    use HasOptions;

    case SubmitDocuments = 'submit_documents';
    case RespondCorrection = 'respond_correction';
    case SubmitPreliminaryHearing = 'submit_preliminary_hearing';
    case SubmitComplaint = 'submit_complaint';
    case ScheduleVisit = 'schedule_visit';
    case OpenTicket = 'open_ticket';
    case WithdrawApplication = 'withdraw_application';
    case ReuseData = 'reuse_data';
    case ConfirmData = 'confirm_data';
    case ViewNotification = 'view_notification';

    public function label(): string
    {
        return match ($this) {
            self::SubmitDocuments => 'Submeter documentos',
            self::RespondCorrection => 'Responder a aperfeiçoamento',
            self::SubmitPreliminaryHearing => 'Submeter audiência prévia',
            self::SubmitComplaint => 'Submeter reclamação',
            self::ScheduleVisit => 'Agendar visita',
            self::OpenTicket => 'Abrir pedido de apoio',
            self::WithdrawApplication => 'Desistir da candidatura',
            self::ReuseData => 'Reutilizar dados',
            self::ConfirmData => 'Confirmar dados',
            self::ViewNotification => 'Consultar notificação',
        };
    }
}
