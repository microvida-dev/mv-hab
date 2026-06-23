<?php

namespace App\Services\Eligibility;

use App\Enums\EligibilityCheckType;
use App\Models\Application;
use App\Models\Contest;
use App\Models\EligibilityCheck;
use App\Models\Program;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class EligibilityCheckService
{
    public function __construct(private readonly EligibilityEngine $engine) {}

    public function candidatePreCheck(
        User $candidate,
        ?Program $program = null,
        ?Contest $contest = null,
    ): EligibilityCheck {
        return $this->engine->run(
            subject: $candidate,
            type: EligibilityCheckType::CandidatePreCheck,
            program: $program,
            contest: $contest,
            actor: $candidate,
        );
    }

    public function formalApplicationCheck(Application $application, User $actor): EligibilityCheck
    {
        $application->loadMissing(['user', 'program', 'contest', 'adhesionRegistration']);

        return $this->engine->run(
            subject: $application->user,
            type: EligibilityCheckType::ApplicationFormalCheck,
            program: $application->program,
            contest: $application->contest,
            application: $application,
            actor: $actor,
        );
    }

    public function rerun(EligibilityCheck $check, User $actor): EligibilityCheck
    {
        $check->loadMissing(['user', 'program', 'contest', 'application']);
        $candidate = $check->user;

        if (! $candidate instanceof User) {
            throw new RuntimeException('Verificação de elegibilidade sem candidato associado.');
        }

        return $this->engine->run(
            subject: $candidate,
            type: EligibilityCheckType::SystemRecheck,
            program: $check->program,
            contest: $check->contest,
            application: $check->application,
            actor: $actor,
        );
    }

    public function latestFor(User $candidate): ?EligibilityCheck
    {
        return $candidate->eligibilityChecks()->with(['program', 'contest', 'results'])->latest()->first();
    }

    /**
     * @return Collection<int, EligibilityCheck>
     */
    public function historyFor(User $candidate): Collection
    {
        return $candidate->eligibilityChecks()->with(['program', 'contest'])->latest()->get();
    }

    /**
     * @return LengthAwarePaginator<int, EligibilityCheck>
     */
    public function paginatedHistoryFor(User $candidate): LengthAwarePaginator
    {
        return $candidate->eligibilityChecks()->with(['program', 'contest'])->latest()->paginate(15);
    }
}
