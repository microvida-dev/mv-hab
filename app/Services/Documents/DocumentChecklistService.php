<?php

namespace App\Services\Documents;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\RequiredDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class DocumentChecklistService
{
    public function __construct(private readonly RequiredDocumentEvaluator $evaluator) {}

    /** @return array<string, mixed> */
    public function forRegistration(
        AdhesionRegistration $registration,
        ?Application $application = null,
        ?Program $program = null,
        ?Contest $contest = null,
    ): array {
        $registration->loadMissing([
            'household.members.incomeRecords.incomeSource',
            'household.incomeRecords.incomeSource',
            'currentHousingSituation',
            'documentSubmissions.documentType',
            'documentSubmissions.currentVersion',
            'documentSubmissions.requiredDocument',
        ]);

        $programId = $application instanceof Application
            ? $application->program_id
            : ($program instanceof Program ? $program->id : ($contest instanceof Contest ? $contest->program_id : null));
        $contestId = $application instanceof Application
            ? $application->contest_id
            : ($contest instanceof Contest ? $contest->id : null);

        $rules = RequiredDocument::query()
            ->with('documentType')
            ->where('is_active', true)
            ->whereHas('documentType', fn ($query) => $query->where('is_active', true))
            ->when(
                $application || $programId || $contestId,
                fn ($query) => $query
                    ->where(fn ($scope) => $scope
                        ->whereNull('program_id')
                        ->orWhere('program_id', $programId))
                    ->where(fn ($scope) => $scope
                        ->whereNull('contest_id')
                        ->orWhere('contest_id', $contestId)),
                fn ($query) => $query
                    ->whereNull('program_id')
                    ->whereNull('contest_id'),
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $items = $rules
            ->flatMap(fn (RequiredDocument $rule) => $this->itemsForRule($registration, $rule, $application))
            ->values();

        $summary = $this->summary($items);

        return [
            'items' => $items,
            'groups' => $items->groupBy('group')->all(),
            'summary' => $summary,
            'next_step' => $this->nextStep($summary),
        ];
    }

    /** @return array<string, mixed> */
    public function forApplication(Application $application): array
    {
        $application->loadMissing('adhesionRegistration');

        $registration = $application->adhesionRegistration;

        return $this->forRegistration($registration, $application);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function itemsForRule(
        AdhesionRegistration $registration,
        RequiredDocument $rule,
        ?Application $application,
    ): Collection {
        $appliesTo = $rule->required_for;
        $documentType = $rule->documentType;
        assert($documentType instanceof DocumentType);

        return $this->targetsFor($registration, $appliesTo, $application)
            ->filter(fn (Model $target) => $this->evaluator->applies($rule, $target))
            ->map(function (Model $target) use ($registration, $rule, $application, $appliesTo, $documentType) {
                $submission = $this->matchingSubmission($registration, $rule, $target, $application, $appliesTo);
                $status = $submission instanceof DocumentSubmission ? $this->documentStatus($submission) : DocumentStatus::Missing;

                return [
                    'key' => $rule->id.'-'.$appliesTo->value.'-'.$target->getKey(),
                    'required_document' => $rule,
                    'document_type' => $documentType,
                    'required_document_id' => $rule->id,
                    'document_type_id' => $rule->document_type_id,
                    'required_for' => $appliesTo,
                    'group' => $this->groupLabel($appliesTo),
                    'target_type' => $appliesTo->value,
                    'target_id' => $target->getKey(),
                    'target_label' => $this->targetLabel($target),
                    'application' => $application,
                    'instructions' => $rule->instructions ?: $documentType->description,
                    'is_required' => $rule->is_required,
                    'submission' => $submission,
                    'status' => $status,
                    'missing' => $submission === null,
                    'rejected' => $status === DocumentStatus::Rejected,
                    'validated' => $status === DocumentStatus::Validated,
                ];
            });
    }

    /**
     * @return Collection<int, mixed>
     */
    private function targetsFor(
        AdhesionRegistration $registration,
        DocumentAppliesTo $appliesTo,
        ?Application $application,
    ): Collection {
        $household = $registration->household instanceof Household ? $registration->household : null;

        return match ($appliesTo) {
            DocumentAppliesTo::AdhesionRegistration, DocumentAppliesTo::General => collect([$registration]),
            DocumentAppliesTo::Household => $household ? collect([$household]) : collect(),
            DocumentAppliesTo::HouseholdMember => $household ? $household->members : collect(),
            DocumentAppliesTo::IncomeRecord => $household ? $household->incomeRecords : collect(),
            DocumentAppliesTo::CurrentHousingSituation => $registration->currentHousingSituation ? collect([$registration->currentHousingSituation]) : collect(),
            DocumentAppliesTo::Application => $application ? collect([$application]) : collect(),
            DocumentAppliesTo::Contract => collect(),
        };
    }

    private function matchingSubmission(
        AdhesionRegistration $registration,
        RequiredDocument $rule,
        Model $target,
        ?Application $application,
        DocumentAppliesTo $appliesTo,
    ): ?DocumentSubmission {
        $query = $registration->documentSubmissions()
            ->with(['documentType', 'currentVersion', 'versions'])
            ->where('required_document_id', $rule->id)
            ->whereNotIn('status', [
                DocumentStatus::Cancelled->value,
                DocumentStatus::Replaced->value,
            ]);

        if ($rule->program_id !== null || $rule->contest_id !== null || $appliesTo === DocumentAppliesTo::Application) {
            $query->where('application_id', $application?->id);
        }

        $this->applyTargetConstraint($query, $appliesTo, $target);

        return $query->latest('updated_at')->latest('id')->first();
    }

    /**
     * @param  HasMany<DocumentSubmission, AdhesionRegistration>  $query
     */
    private function applyTargetConstraint($query, DocumentAppliesTo $appliesTo, Model $target): void
    {
        match ($appliesTo) {
            DocumentAppliesTo::Household => $query->where('household_id', $target->getKey()),
            DocumentAppliesTo::HouseholdMember => $query->where('household_member_id', $target->getKey()),
            DocumentAppliesTo::IncomeRecord => $query->where('income_record_id', $target->getKey()),
            DocumentAppliesTo::CurrentHousingSituation => $query->where('current_housing_situation_id', $target->getKey()),
            DocumentAppliesTo::Application => $query->where('application_id', $target->getKey()),
            default => null,
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array{total_required: int, missing: int, submitted: int, validated: int, rejected: int, percentage: int}
     */
    private function summary(Collection $items): array
    {
        $required = $items->where('is_required', true);
        $missing = $required->where('missing', true)->count();
        $rejected = $required->where('rejected', true)->count();
        $validated = $required->where('validated', true)->count();
        $submitted = $required
            ->filter(fn (array $item) => in_array($item['status'], [
                DocumentStatus::Submitted,
                DocumentStatus::UnderReview,
                DocumentStatus::Validated,
            ], true))
            ->count();
        $complete = max(0, $required->count() - $missing - $rejected);

        return [
            'total_required' => $required->count(),
            'missing' => $missing,
            'submitted' => $submitted,
            'validated' => $validated,
            'rejected' => $rejected,
            'percentage' => $required->isEmpty() ? 100 : (int) round(($complete / $required->count()) * 100),
        ];
    }

    /**
     * @param  array{missing: int, rejected: int}  $summary
     */
    private function nextStep(array $summary): string
    {
        if ($summary['missing'] > 0) {
            return 'Existem documentos obrigatórios em falta. Aceda à checklist documental para completar o seu processo.';
        }

        if ($summary['rejected'] > 0) {
            return 'Existem documentos rejeitados que devem ser substituídos.';
        }

        return 'A documentação obrigatória encontra-se submetida para preparação de futuras candidaturas.';
    }

    private function groupLabel(DocumentAppliesTo $appliesTo): string
    {
        return match ($appliesTo) {
            DocumentAppliesTo::AdhesionRegistration, DocumentAppliesTo::General => 'Documentos do registo',
            DocumentAppliesTo::Household => 'Documentos do agregado',
            DocumentAppliesTo::HouseholdMember => 'Documentos por membro do agregado',
            DocumentAppliesTo::IncomeRecord => 'Documentos de rendimentos',
            DocumentAppliesTo::CurrentHousingSituation => 'Documentos da situação habitacional',
            DocumentAppliesTo::Application => 'Documentos da candidatura',
            DocumentAppliesTo::Contract => 'Documentos contratuais',
        };
    }

    private function targetLabel(Model $target): string
    {
        return match (true) {
            $target instanceof AdhesionRegistration => 'Registo de Adesão',
            $target instanceof Household => (string) ($target->getAttribute('name') ?: 'Agregado familiar'),
            $target instanceof HouseholdMember => $target->full_name,
            $target instanceof IncomeRecord => trim(((string) ($target->incomeSource?->getAttribute('name') ?: 'Rendimento')).' '.number_format((float) $target->monthly_amount, 2, ',', '.').' €/mês'),
            $target instanceof CurrentHousingSituation => $this->housingStatusLabel($target),
            $target instanceof Application => $target->application_number ?: 'Candidatura em rascunho',
            default => class_basename($target).' #'.$target->getKey(),
        };
    }

    private function documentStatus(DocumentSubmission $submission): DocumentStatus
    {
        return $submission->status ?? DocumentStatus::Missing;
    }

    private function housingStatusLabel(CurrentHousingSituation $target): string
    {
        $status = $target->housing_status;

        return $status?->label() ?? 'Situação habitacional';
    }
}
