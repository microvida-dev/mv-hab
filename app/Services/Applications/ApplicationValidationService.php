<?php

namespace App\Services\Applications;

use App\Enums\AdhesionRegistrationStatus;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\HouseholdMember;
use App\Models\User;
use App\Services\Documents\DocumentChecklistService;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApplicationValidationService
{
    public function __construct(
        private readonly DocumentChecklistService $documentChecklistService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function readinessForStart(User $user, Contest $contest): array
    {
        $registration = $user->adhesionRegistration()
            ->with([
                'household.members.incomeRecords',
                'household.incomeRecords',
                'currentHousingSituation',
            ])
            ->first();

        /** @var Collection<int, HouseholdMember>|null $registrationMembers */
        $registrationMembers = $registration?->household?->members;

        $checks = [
            $this->check(
                'contest',
                $contest->isOpenForApplications(),
                'O concurso não está aberto para candidaturas.',
                'public.contests.show',
                ['slug' => $contest->slug],
            ),
            $this->check(
                'registration',
                $registration?->status === AdhesionRegistrationStatus::Registered,
                $registration === null
                    ? 'Crie o seu Registo de Adesão antes de iniciar uma candidatura.'
                    : 'Complete e finalize o seu Registo de Adesão antes de iniciar uma candidatura.',
                $registration ? 'candidate.registration.show' : 'candidate.registration.create',
            ),
            $this->check(
                'household',
                $registration?->household !== null
                    && $registration->household->members->contains('is_applicant', true),
                'Complete o agregado e confirme o membro requerente.',
                'candidate.household.show',
            ),
            $this->check(
                'income',
                $this->incomeIsComplete($registrationMembers),
                'Declare rendimentos ou ausência de rendimentos para todos os membros.',
                'candidate.income-records.index',
            ),
            $this->check(
                'housing',
                $registration?->currentHousingSituation !== null,
                'Preencha a sua situação habitacional atual.',
                'candidate.current-housing.show',
            ),
            $this->check(
                'duplicate',
                ! $this->hasActiveDuplicate($user, $contest),
                'Já existe uma candidatura ativa para este concurso.',
                'candidate.applications.index',
            ),
        ];

        return [
            'ready' => $this->allChecksPassed($checks),
            'checks' => $checks,
            'registration' => $registration,
        ];
    }

    public function validateStart(User $user, Contest $contest): void
    {
        $readiness = $this->readinessForStart($user, $contest);

        if (! $readiness['ready']) {
            throw ValidationException::withMessages([
                'application' => $this->failedMessages($readiness['checks']),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function readinessForSubmission(Application $application): array
    {
        $application->loadMissing([
            'contest',
            'adhesionRegistration.household.members.incomeRecords',
            'adhesionRegistration.household.incomeRecords',
            'adhesionRegistration.currentHousingSituation',
        ]);

        $registration = $application->adhesionRegistration;
        $documentChecklist = $this->documentChecklistService->forApplication($application);
        /** @var list<array{is_required: bool, status: DocumentStatus|string|null}> $documentItems */
        $documentItems = $documentChecklist['items'] ?? [];
        $blockingDocuments = collect($documentItems)
            ->where('is_required', true)
            ->filter(fn (array $item) => ! in_array($item['status'], [
                DocumentStatus::Submitted,
                DocumentStatus::UnderReview,
                DocumentStatus::Validated,
            ], true))
            ->values();
        /** @var Collection<int, HouseholdMember>|null $registrationMembers */
        $registrationMembers = $registration->household?->members;

        $checks = [
            $this->check('draft', $application->status === ApplicationStatus::Draft, 'A candidatura já não está em rascunho.'),
            $this->check('contest', $application->contest->isOpenForApplications(), 'O período de candidatura terminou.'),
            $this->check('registration', $registration->status === AdhesionRegistrationStatus::Registered, 'O Registo de Adesão não está finalizado.'),
            $this->check(
                'household',
                $registration->household !== null
                    && $registration->household->members->contains('is_applicant', true),
                'O agregado familiar não está completo.',
            ),
            $this->check(
                'income',
                $this->incomeIsComplete($registrationMembers),
                'A informação de rendimentos não está completa.',
            ),
            $this->check('housing', $registration->currentHousingSituation !== null, 'A situação habitacional não está preenchida.'),
            $this->check(
                'documents',
                $blockingDocuments->isEmpty(),
                'Existem documentos obrigatórios em falta, rejeitados, expirados ou cancelados.',
            ),
        ];

        return [
            'ready' => $this->allChecksPassed($checks),
            'checks' => $checks,
            'documents' => $documentChecklist,
            'blocking_documents' => $blockingDocuments,
            'eligibility_pre_check' => $this->runEligibilityPreCheck($application),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validateSubmission(Application $application): array
    {
        $readiness = $this->readinessForSubmission($application);

        if (! $readiness['ready']) {
            throw ValidationException::withMessages([
                'application' => $this->failedMessages($readiness['checks']),
            ]);
        }

        return $readiness;
    }

    /**
     * @return array<string, mixed>
     */
    public function runEligibilityPreCheck(Application $application): array
    {
        $check = $application->latestEligibilityCheck()->first();

        if ($check) {
            return [
                'status' => $this->enumValue($check->result),
                'message' => $check->summary,
                'check_id' => $check->id,
            ];
        }

        return [
            'status' => 'not_available',
            'message' => 'Ainda não existe uma verificação de elegibilidade associada. A submissão não constitui decisão de elegibilidade.',
        ];
    }

    private function hasActiveDuplicate(User $user, Contest $contest): bool
    {
        $activeStatuses = collect(ApplicationStatus::cases())
            ->filter(fn (ApplicationStatus $status) => $status->isActive())
            ->map->value
            ->all();

        return Application::query()
            ->forUser($user)
            ->where('contest_id', $contest->id)
            ->whereIn('status', $activeStatuses)
            ->exists();
    }

    /**
     * @param  Collection<int, HouseholdMember>|null  $members
     */
    private function incomeIsComplete(?Collection $members): bool
    {
        return $members !== null
            && $members->isNotEmpty()
            && $members->every(fn (HouseholdMember $member) => $member->has_no_income || $member->incomeRecords->isNotEmpty());
    }

    /**
     * @param  array<string, string>  $routeParameters
     * @return array{key: string, passed: bool, message: string, successMessage: string, route: string|null, routeParameters: array<string, string>}
     */
    private function check(
        string $key,
        bool $passed,
        string $message,
        ?string $route = null,
        array $routeParameters = [],
    ): array {
        $successMessage = match ($key) {
            'contest' => 'O concurso está aberto para candidaturas.',
            'registration' => 'O Registo de Adesão está finalizado.',
            'household' => 'O agregado familiar e o requerente estão confirmados.',
            'income' => 'A informação de rendimentos está completa.',
            'housing' => 'A situação habitacional está preenchida.',
            'duplicate' => 'Não existe outra candidatura ativa para este concurso.',
            'draft' => 'A candidatura está em rascunho e pode ser submetida.',
            'documents' => 'A documentação obrigatória está submetida ou validada.',
            default => 'Verificação concluída.',
        };

        return compact(
            'key',
            'passed',
            'message',
            'successMessage',
            'route',
            'routeParameters',
        );
    }

    /**
     * @param  array<int, array{passed: bool}>  $checks
     */
    private function allChecksPassed(array $checks): bool
    {
        foreach ($checks as $check) {
            if (! $check['passed']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array{passed: bool, message: string}>  $checks
     * @return list<string>
     */
    private function failedMessages(array $checks): array
    {
        $messages = [];

        foreach ($checks as $check) {
            if (! $check['passed']) {
                $messages[] = $check['message'];
            }
        }

        return $messages;
    }

    private function enumValue(mixed $value): string|int|null
    {
        return $value instanceof BackedEnum ? $value->value : null;
    }
}
