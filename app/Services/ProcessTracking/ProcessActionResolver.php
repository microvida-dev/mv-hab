<?php

namespace App\Services\ProcessTracking;

use App\Enums\CorrectionRequestStatus;
use App\Enums\HearingStatus;
use App\Enums\ProcessActionStatus;
use App\Enums\ProcessActionType;
use App\Models\Application;
use Illuminate\Support\Collection;

class ProcessActionResolver
{
    /**
     * @return Collection<int, array{type: string, status: string, title: string, description: string|null, due_at: mixed, route: string|null}>
     */
    public function forApplication(Application $application): Collection
    {
        $application->loadMissing(['correctionRequests', 'hearings', 'processActions']);
        $actions = collect();

        foreach ($application->correctionRequests as $request) {
            if ($request->candidate_visible && in_array($request->status, [CorrectionRequestStatus::Issued, CorrectionRequestStatus::Open], true)) {
                $actions->push([
                    'type' => ProcessActionType::RespondCorrection->value,
                    'status' => ProcessActionStatus::Available->value,
                    'title' => 'Responder a pedido de aperfeiçoamento',
                    'description' => $request->subject,
                    'due_at' => $request->response_deadline_at,
                    'route' => route('candidate.correction-requests.respond', $request, false),
                ]);
            }
        }

        foreach ($application->hearings as $hearing) {
            if ($hearing->candidate_visible && $hearing->status === HearingStatus::Open) {
                $actions->push([
                    'type' => ProcessActionType::SubmitPreliminaryHearing->value,
                    'status' => ProcessActionStatus::Available->value,
                    'title' => 'Submeter pronúncia de audiência prévia',
                    'description' => $hearing->subject,
                    'due_at' => $hearing->deadline_at,
                    'route' => route('candidate.hearings.submit', $hearing, false),
                ]);
            }
        }

        if ($application->status->canBeWithdrawn()) {
            $actions->push([
                'type' => ProcessActionType::WithdrawApplication->value,
                'status' => ProcessActionStatus::Available->value,
                'title' => 'Desistir da candidatura',
                'description' => 'Disponível mediante confirmação explícita.',
                'due_at' => null,
                'route' => route('candidate.controlled-withdrawals.create', $application, false),
            ]);
        }

        return $actions->values();
    }
}
