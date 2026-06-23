<?php

namespace App\Services\OperationalReports;

use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\Contest;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\EligibilityCheck;
use App\Models\Household;
use App\Models\ProcessConfirmation;
use App\Models\ProcessTimelineEvent;
use App\Models\User;
use BackedEnum;
use Countable;
use DateTimeInterface;
use Illuminate\Support\Collection;

class ApplicationReportPayloadBuilder
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function build(Application $application, User $actor, array $options = []): array
    {
        $application->loadMissing([
            'user',
            'contest',
            'program',
            'household.members',
            'documentSubmissions.documentType',
            'latestEligibilityCheck',
            'applicationScores',
            'processTimelineEvents',
            'correctionRequests',
            'officialNotifications',
            'processConfirmations',
        ]);

        $timeline = (bool) ($options['include_timeline'] ?? true)
            ? $this->timelineRows($application)
            : [];
        $contest = $application->getRelationValue('contest');
        $candidate = $application->getRelationValue('user');
        $household = $application->getRelationValue('household');
        $processConfirmation = $this->firstRelated($application, 'processConfirmations');
        $latestEligibilityCheck = $application->getRelationValue('latestEligibilityCheck');
        $latestScore = $this->latestScore($application);

        return [
            'generated_at' => now()->toDateTimeString(),
            'generated_by' => $actor->id,
            'copy' => 'Este documento foi gerado automaticamente com base nos dados registados na plataforma à data da emissão. A validação final compete aos serviços municipais.',
            'application' => [
                'id' => $application->id,
                'public_id' => $application->public_id,
                'application_number' => $application->application_number,
                'process_number' => $processConfirmation instanceof ProcessConfirmation ? $processConfirmation->getAttribute('process_number') : null,
                'status' => $this->enumValue($application->getAttribute('status')),
                'submitted_at' => $this->dateTimeString($application->getAttribute('submitted_at')),
            ],
            'contest' => [
                'id' => $contest instanceof Contest ? $contest->id : null,
                'code' => $contest instanceof Contest ? $contest->code : null,
                'title' => $contest instanceof Contest ? $contest->title : null,
            ],
            'candidate' => [
                'id' => $candidate instanceof User ? $candidate->id : null,
                'name' => $actor->hasPermission('reports.view_sensitive') && $candidate instanceof User ? $candidate->name : 'Candidato',
            ],
            'household' => [
                'members_count' => $household instanceof Household ? $this->relationCount($household, 'members') : 0,
            ],
            'documents' => $this->documentRows($application),
            'eligibility' => [
                'latest_status' => $latestEligibilityCheck instanceof EligibilityCheck ? $this->enumValue($latestEligibilityCheck->getAttribute('status')) : null,
            ],
            'scoring' => [
                'scores_count' => $this->relationCount($application, 'applicationScores'),
                'latest_total' => $latestScore instanceof ApplicationScore ? $latestScore->getAttribute('total_score') : null,
            ],
            'timeline' => $timeline,
            'corrections_count' => $this->relationCount($application, 'correctionRequests'),
            'notifications_count' => $this->relationCount($application, 'officialNotifications'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function timelineRows(Application $application): array
    {
        $events = $application->getRelationValue('processTimelineEvents');

        if (! $events instanceof Collection) {
            return [];
        }

        return $events->take(30)
            ->filter(fn (mixed $event): bool => $event instanceof ProcessTimelineEvent)
            ->map(fn (ProcessTimelineEvent $event): array => [
                'type' => $this->enumValue($event->getAttribute('event_type')),
                'title' => $event->getAttribute('title'),
                'occurred_at' => $this->dateTimeString($event->getAttribute('occurred_at')),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function documentRows(Application $application): array
    {
        $documents = $application->getRelationValue('documentSubmissions');

        if (! $documents instanceof Collection) {
            return [];
        }

        return $documents
            ->filter(fn (mixed $document): bool => $document instanceof DocumentSubmission)
            ->map(function (DocumentSubmission $document): array {
                $documentType = $document->getRelationValue('documentType');

                return [
                    'title' => $document->getAttribute('title'),
                    'type' => $documentType instanceof DocumentType ? $documentType->name : null,
                    'status' => $this->enumValue($document->getAttribute('status')),
                    'submitted_at' => $this->dateTimeString($document->getAttribute('submitted_at')),
                ];
            })
            ->values()
            ->all();
    }

    private function firstRelated(Application $application, string $relation): mixed
    {
        $related = $application->getRelationValue($relation);

        return $related instanceof Collection ? $related->first() : null;
    }

    private function latestScore(Application $application): ?ApplicationScore
    {
        $scores = $application->getRelationValue('applicationScores');

        if (! $scores instanceof Collection) {
            return null;
        }

        $score = $scores->sortByDesc('id')->first();

        return $score instanceof ApplicationScore ? $score : null;
    }

    private function relationCount(Application|Household $model, string $relation): int
    {
        $related = $model->getRelationValue($relation);

        return $related instanceof Countable ? count($related) : 0;
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private function dateTimeString(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : null;
    }
}
