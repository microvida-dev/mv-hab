<?php

namespace App\Services\Documents;

use App\Enums\DocumentAppliesTo;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contract;
use App\Models\DocumentSubmission;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\RequiredDocument;

final class DocumentSubmissionContextResolver
{
    /**
     * @return array{
     *     required_document_id:int|null,
     *     target_type:string,
     *     target_id:int|null,
     *     target_label:string,
     *     requirement_instance:int,
     *     required_submissions:int,
     *     position_label:string|null,
     *     reference_period:string|null
     * }
     */
    public function resolve(
        DocumentSubmission $submission,
    ): array {
        $requiredDocument = $this->requiredDocument(
            $submission,
        );

        $appliesTo = $requiredDocument?->required_for;

        if (! $appliesTo instanceof DocumentAppliesTo) {
            $appliesTo = $this->detectedAppliesTo(
                $submission,
            );
        }

        $requirementInstance = max(
            1,
            (int) $submission->requirement_instance,
        );

        $requiredSubmissions = $requiredDocument
            instanceof RequiredDocument
                ? max(
                    1,
                    (int) $requiredDocument
                        ->required_submissions,
                )
                : 1;

        return [
            'required_document_id' => $submission->required_document_id,

            'target_type' => $appliesTo->value,
            'target_id' => $this->targetId(
                $submission,
                $appliesTo,
            ),

            'target_label' => $this->targetLabel(
                $submission,
                $appliesTo,
            ),

            'requirement_instance' => $requirementInstance,

            'required_submissions' => $requiredSubmissions,

            'position_label' => $requiredSubmissions > 1
                ? $requirementInstance
                    .'/'
                    .$requiredSubmissions
                : null,

            'reference_period' => $submission->reference_period
                ?->toDateString(),
        ];
    }

    public function identity(
        DocumentSubmission $submission,
    ): string {
        $context = $this->resolve($submission);

        return implode('|', [
            $context['required_document_id'] ?? '',
            $context['target_type'],
            $context['target_id'] ?? '',
            $context['requirement_instance'],
        ]);
    }

    private function requiredDocument(
        DocumentSubmission $submission,
    ): ?RequiredDocument {
        $requiredDocument = $submission->getRelationValue(
            'requiredDocument',
        );

        return $requiredDocument instanceof RequiredDocument
            ? $requiredDocument
            : null;
    }

    private function targetId(
        DocumentSubmission $submission,
        DocumentAppliesTo $appliesTo,
    ): ?int {
        return match ($appliesTo) {
            DocumentAppliesTo::General,
            DocumentAppliesTo::AdhesionRegistration => $submission->adhesion_registration_id,

            DocumentAppliesTo::Household => $submission->household_id,

            DocumentAppliesTo::HouseholdMember => $submission->household_member_id,

            DocumentAppliesTo::IncomeRecord => $submission->income_record_id,

            DocumentAppliesTo::CurrentHousingSituation => $submission->current_housing_situation_id,

            DocumentAppliesTo::Application => $submission->application_id,

            DocumentAppliesTo::Contract => $submission->contract_id,
        };
    }

    private function targetLabel(
        DocumentSubmission $submission,
        DocumentAppliesTo $appliesTo,
    ): string {
        return match ($appliesTo) {
            DocumentAppliesTo::General,
            DocumentAppliesTo::AdhesionRegistration => $this->registrationLabel($submission),

            DocumentAppliesTo::Household => $this->householdLabel($submission),

            DocumentAppliesTo::HouseholdMember => $this->householdMemberLabel($submission),

            DocumentAppliesTo::IncomeRecord => $this->incomeRecordLabel($submission),

            DocumentAppliesTo::CurrentHousingSituation => 'Situação habitacional atual',

            DocumentAppliesTo::Application => $this->applicationLabel($submission),

            DocumentAppliesTo::Contract => $this->contractLabel($submission),
        };
    }

    private function registrationLabel(
        DocumentSubmission $submission,
    ): string {
        $registration = $submission->getRelationValue(
            'adhesionRegistration',
        );

        if (
            $registration instanceof AdhesionRegistration
            && filled($registration->full_name)
        ) {
            return (string) $registration->full_name;
        }

        return 'Registo de adesão';
    }

    private function householdLabel(
        DocumentSubmission $submission,
    ): string {
        $household = $submission->getRelationValue(
            'household',
        );

        if (
            $household instanceof Household
            && filled($household->name)
        ) {
            return (string) $household->name;
        }

        return 'Agregado familiar';
    }

    private function householdMemberLabel(
        DocumentSubmission $submission,
    ): string {
        $member = $submission->getRelationValue(
            'householdMember',
        );

        if (
            $member instanceof HouseholdMember
            && filled($member->full_name)
        ) {
            return (string) $member->full_name;
        }

        return 'Elemento do agregado';
    }

    private function incomeRecordLabel(
        DocumentSubmission $submission,
    ): string {
        $incomeRecord = $submission->getRelationValue(
            'incomeRecord',
        );

        if (! $incomeRecord instanceof IncomeRecord) {
            return 'Rendimento';
        }

        if (filled($incomeRecord->description)) {
            return (string) $incomeRecord->description;
        }

        $incomeSource = $incomeRecord->getRelationValue(
            'incomeSource',
        );

        if (
            $incomeSource instanceof IncomeSource
            && filled($incomeSource->name)
        ) {
            return (string) $incomeSource->name;
        }

        return 'Rendimento';
    }

    private function applicationLabel(
        DocumentSubmission $submission,
    ): string {
        $application = $submission->getRelationValue(
            'application',
        );

        if (
            $application instanceof Application
            && filled($application->application_number)
        ) {
            return (string) $application
                ->application_number;
        }

        return 'Candidatura';
    }

    private function contractLabel(
        DocumentSubmission $submission,
    ): string {
        $contract = $submission->getRelationValue(
            'contract',
        );

        if (
            $contract instanceof Contract
            && filled($contract->contract_number)
        ) {
            return (string) $contract->contract_number;
        }

        return 'Contrato';
    }

    private function detectedAppliesTo(
        DocumentSubmission $submission,
    ): DocumentAppliesTo {
        return match (true) {
            $submission->income_record_id !== null => DocumentAppliesTo::IncomeRecord,

            $submission->household_member_id !== null => DocumentAppliesTo::HouseholdMember,

            $submission
                ->current_housing_situation_id !== null => DocumentAppliesTo::CurrentHousingSituation,

            $submission->household_id !== null => DocumentAppliesTo::Household,

            $submission->contract_id !== null => DocumentAppliesTo::Contract,

            $submission->application_id !== null => DocumentAppliesTo::Application,

            default => DocumentAppliesTo::AdhesionRegistration,
        };
    }
}
