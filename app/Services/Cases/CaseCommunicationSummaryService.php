<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseCommunicationData;
use App\Models\Complaint;
use App\Models\DataSubjectRequest;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CaseCommunicationSummaryService
{
    public function __construct(private readonly CaseAuthorizationService $authorization) {}

    /**
     * @return list<CaseCommunicationData>
     */
    public function forCase(User $user, Model $case): array
    {
        if ($case instanceof SupportTicket && $this->authorization->hasPermission($user, 'support.view')) {
            return [
                new CaseCommunicationData(
                    label: 'Mensagens do pedido',
                    description: $case->messages()->count().' mensagens autorizadas. Conteúdo sensível não é resumido neste painel.',
                    date: $this->asCarbon($case->last_message_at),
                    source: 'apoio',
                ),
            ];
        }

        if ($case instanceof Complaint && $this->authorization->hasPermission($user, 'complaints.view')) {
            return [
                new CaseCommunicationData(
                    label: 'Tramitação da reclamação',
                    description: $case->additionalInformationRequests()->count().' pedidos de informação complementar registados.',
                    date: $this->asCarbon($case->updated_at),
                    source: 'reclamações',
                ),
            ];
        }

        if ($case instanceof DataSubjectRequest && $this->authorization->hasPermission($user, 'privacy.view')) {
            return [
                new CaseCommunicationData(
                    label: 'Ações do pedido RGPD',
                    description: $case->actions()->count().' ações registadas no fluxo de titular.',
                    date: $this->asCarbon($case->updated_at),
                    source: 'rgpd',
                ),
            ];
        }

        return [];
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return $value === null ? null : Carbon::parse($value);
    }
}
