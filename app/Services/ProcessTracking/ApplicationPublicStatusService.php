<?php

namespace App\Services\ProcessTracking;

use App\Enums\ApplicationStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\HearingStatus;
use App\Enums\PublicProcessStatus;
use App\Models\Application;
use App\Models\ApplicationPublicStatusSnapshot;
use Illuminate\Support\Carbon;

class ApplicationPublicStatusService
{
    public function refresh(Application $application): ApplicationPublicStatusSnapshot
    {
        $application->loadMissing(['correctionRequests', 'hearings']);
        $status = $this->statusFor($application);
        $payload = $this->payloadFor($status, $application);
        $applicationStatus = $application->getAttribute('status');

        /** @var ApplicationPublicStatusSnapshot $snapshot */
        $snapshot = ApplicationPublicStatusSnapshot::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'public_status' => $status->value,
                'internal_status' => $applicationStatus instanceof ApplicationStatus
                    ? $applicationStatus->value
                    : (is_string($applicationStatus) ? $applicationStatus : null),
                'title' => $payload['title'],
                'description' => $payload['description'],
                'next_step' => $payload['next_step'],
                'action_required' => $payload['action_required'],
                'action_due_at' => $payload['action_due_at'],
                'progress_percentage' => $payload['progress_percentage'],
                'is_terminal' => $status->isTerminal(),
            ],
        );

        return $snapshot;
    }

    public function statusFor(Application $application): PublicProcessStatus
    {
        $openHearing = $application->hearings
            ->first(fn ($hearing) => $hearing->candidate_visible && $hearing->status === HearingStatus::Open);

        if ($openHearing !== null) {
            return PublicProcessStatus::AwaitingPreliminaryHearing;
        }

        $submittedHearing = $application->hearings
            ->first(fn ($hearing) => $hearing->candidate_visible && $hearing->status === HearingStatus::Submitted);

        if ($submittedHearing !== null) {
            return PublicProcessStatus::PreliminaryHearingSubmitted;
        }

        $openCorrection = $application->correctionRequests
            ->first(fn ($request) => $request->candidate_visible && in_array($request->status, [
                CorrectionRequestStatus::Issued,
                CorrectionRequestStatus::Open,
                CorrectionRequestStatus::PartiallyResponded,
            ], true));

        if ($openCorrection !== null) {
            return PublicProcessStatus::AwaitingCorrection;
        }

        return match ($application->status) {
            ApplicationStatus::Draft => PublicProcessStatus::Draft,
            ApplicationStatus::Submitted => PublicProcessStatus::Submitted,
            ApplicationStatus::UnderReview => PublicProcessStatus::UnderReview,
            ApplicationStatus::RequiresCorrection => PublicProcessStatus::AwaitingCorrection,
            ApplicationStatus::CorrectionSubmitted => PublicProcessStatus::CorrectionSubmitted,
            ApplicationStatus::Eligible => PublicProcessStatus::Admitted,
            ApplicationStatus::Ineligible, ApplicationStatus::Excluded => PublicProcessStatus::NotAdmitted,
            ApplicationStatus::Cancelled => PublicProcessStatus::Cancelled,
            ApplicationStatus::Withdrawn => PublicProcessStatus::Withdrawn,
            ApplicationStatus::Expired => PublicProcessStatus::Archived,
        };
    }

    /**
     * @return array{title: string, description: string, next_step: string|null, action_required: bool, action_due_at: Carbon|null, progress_percentage: int}
     */
    private function payloadFor(PublicProcessStatus $status, Application $application): array
    {
        $dueAt = null;
        if ($status === PublicProcessStatus::AwaitingCorrection) {
            $dueAt = $application->correctionRequests
                ->where('candidate_visible', true)
                ->sortBy('response_deadline_at')
                ->first()?->response_deadline_at;
        }

        if ($status === PublicProcessStatus::AwaitingPreliminaryHearing) {
            $dueAt = $application->hearings
                ->where('candidate_visible', true)
                ->sortBy('deadline_at')
                ->first()?->deadline_at;
        }

        return [
            'title' => $status->label(),
            'description' => match ($status) {
                PublicProcessStatus::Draft => 'A candidatura ainda está em preparação e pode ser revista antes da submissão.',
                PublicProcessStatus::Submitted => 'A candidatura foi submetida e aguarda receção ou análise pelos serviços municipais.',
                PublicProcessStatus::AwaitingCorrection => 'Existe um pedido de aperfeiçoamento ou resposta pendente do candidato.',
                PublicProcessStatus::AwaitingPreliminaryHearing => 'Existe audiência prévia aberta para pronúncia dentro do prazo.',
                PublicProcessStatus::Withdrawn => 'A candidatura foi desistida pelo candidato.',
                default => 'O processo encontra-se na fase indicada com base nos atos registados na plataforma.',
            },
            'next_step' => match ($status) {
                PublicProcessStatus::Draft => 'Rever dados, documentos e declarações antes de submeter.',
                PublicProcessStatus::AwaitingCorrection => 'Responder ao pedido de aperfeiçoamento dentro do prazo indicado.',
                PublicProcessStatus::AwaitingPreliminaryHearing => 'Submeter pronúncia de audiência prévia, se pretender.',
                PublicProcessStatus::Submitted, PublicProcessStatus::UnderReview => 'Aguardar análise dos serviços municipais.',
                default => null,
            },
            'action_required' => in_array($status, [PublicProcessStatus::Draft, PublicProcessStatus::AwaitingCorrection, PublicProcessStatus::AwaitingPreliminaryHearing], true),
            'action_due_at' => $dueAt instanceof Carbon ? $dueAt : null,
            'progress_percentage' => $this->progressFor($status),
        ];
    }

    private function progressFor(PublicProcessStatus $status): int
    {
        return match ($status) {
            PublicProcessStatus::Draft => 10,
            PublicProcessStatus::Submitted, PublicProcessStatus::Received => 25,
            PublicProcessStatus::UnderReview, PublicProcessStatus::AwaitingDocuments, PublicProcessStatus::AwaitingCorrection => 45,
            PublicProcessStatus::AwaitingPreliminaryHearing, PublicProcessStatus::PreliminaryHearingSubmitted => 55,
            PublicProcessStatus::Admitted, PublicProcessStatus::Scoring, PublicProcessStatus::Ranked => 70,
            PublicProcessStatus::ProvisionalListPublished, PublicProcessStatus::ComplaintPeriod, PublicProcessStatus::ComplaintSubmitted => 80,
            PublicProcessStatus::DefinitiveListPublished, PublicProcessStatus::Allocated, PublicProcessStatus::ContractPending => 90,
            PublicProcessStatus::Completed => 100,
            PublicProcessStatus::Withdrawn, PublicProcessStatus::Cancelled, PublicProcessStatus::Archived, PublicProcessStatus::NotAdmitted, PublicProcessStatus::NotAllocated => 100,
            default => 35,
        };
    }
}
