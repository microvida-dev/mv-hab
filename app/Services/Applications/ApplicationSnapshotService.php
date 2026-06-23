<?php

namespace App\Services\Applications;

use App\Enums\ApplicationSnapshotType;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ApplicationSnapshotService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function create(Application $application): void
    {
        $application->loadMissing([
            'adhesionRegistration',
            'household.members.incomeRecords.incomeSource',
            'household.incomeRecords.incomeSource',
            'household.incomeRecords.householdMember',
            'currentHousingSituation',
            'applicationDocuments.documentSubmission.currentVersion',
            'applicationDocuments.documentType',
            'contest',
            'program',
        ]);

        $registration = $application->adhesionRegistration;
        $household = $application->household;
        $currentHousingSituation = $application->currentHousingSituation;

        if ($household === null || $currentHousingSituation === null) {
            throw ValidationException::withMessages([
                'application' => 'A candidatura deve ter agregado e situação habitacional para gerar snapshots.',
            ]);
        }

        /** @var EloquentCollection<int, HouseholdMember> $householdMembers */
        $householdMembers = $household->members;
        /** @var EloquentCollection<int, IncomeRecord> $incomeRecords */
        $incomeRecords = $household->incomeRecords;
        /** @var EloquentCollection<int, ApplicationDocument> $applicationDocuments */
        $applicationDocuments = $application->applicationDocuments;

        $snapshots = [
            ApplicationSnapshotType::AdhesionRegistration->value => Arr::only(
                $registration->attributesToArray(),
                [
                    'full_name', 'email', 'phone', 'mobile_phone', 'document_type',
                    'document_number', 'document_valid_until', 'nif', 'birth_date',
                    'nationality', 'address', 'postal_code', 'city', 'parish',
                    'municipality', 'wants_email_notifications', 'wants_sms_notifications',
                    'wants_postal_notifications',
                ],
            ),
            ApplicationSnapshotType::Household->value => [
                'name' => $household->name,
                'household_type' => $household->household_type,
                'members_count' => $householdMembers->count(),
                'monthly_income' => $household->totalMonthlyIncome(),
                'annual_income' => $household->totalAnnualIncome(),
            ],
            ApplicationSnapshotType::HouseholdMembers->value => $householdMembers
                ->map(fn (HouseholdMember $member) => Arr::only($member->attributesToArray(), [
                    'is_applicant', 'full_name', 'birth_date', 'gender', 'relationship',
                    'nationality', 'document_type', 'document_number', 'document_valid_until',
                    'nif', 'marital_status', 'professional_status', 'qualification_level', 'employment_type',
                    'works_in_municipality', 'is_dependent', 'is_student', 'is_disabled',
                    'has_multiple_disabilities', 'is_pregnant', 'is_exempt_from_irs',
                    'disability_percentage', 'has_reduced_mobility', 'is_informal_caregiver',
                    'is_elderly', 'has_no_income', 'no_income_reason',
                ]))
                ->values()
                ->all(),
            ApplicationSnapshotType::IncomeRecords->value => $incomeRecords
                ->map(function (IncomeRecord $income): array {
                    /** @var HouseholdMember|null $householdMember */
                    $householdMember = $income->getRelationValue('householdMember');
                    /** @var IncomeSource|null $incomeSource */
                    $incomeSource = $income->getRelationValue('incomeSource');

                    return [
                        'member_name' => $householdMember?->full_name,
                        'source_code' => $incomeSource?->code,
                        'source_name' => $incomeSource?->name,
                        ...Arr::only($income->attributesToArray(), [
                            'description', 'monthly_amount', 'annual_amount', 'reference_year',
                            'starts_at', 'ends_at', 'is_current', 'is_taxable',
                        ]),
                    ];
                })
                ->values()
                ->all(),
            ApplicationSnapshotType::CurrentHousingSituation->value => Arr::only(
                $currentHousingSituation->attributesToArray(),
                [
                    'housing_status', 'current_address', 'current_postal_code', 'current_city',
                    'current_parish', 'current_municipality', 'resides_in_municipality',
                    'residence_years_in_municipality', 'works_in_municipality',
                    'workplace_municipality', 'current_housing_typology',
                    'current_housing_rooms', 'current_housing_condition',
                    'current_monthly_rent', 'current_housing_expense', 'is_overcrowded',
                    'is_at_risk_of_eviction', 'is_homeless', 'is_temporary_accommodation',
                    'is_domestic_violence_victim', 'has_accessibility_needs',
                    'has_high_rent_burden', 'request_reason',
                ],
            ),
            ApplicationSnapshotType::Documents->value => $applicationDocuments
                ->map(function (ApplicationDocument $document): array {
                    /** @var DocumentType $documentType */
                    $documentType = $document->getRelationValue('documentType');
                    /** @var DocumentSubmission $documentSubmission */
                    $documentSubmission = $document->getRelationValue('documentSubmission');
                    /** @var DocumentVersion|null $currentVersion */
                    $currentVersion = $documentSubmission->getRelationValue('currentVersion');

                    return [
                        'document_submission_id' => $document->document_submission_id,
                        'document_type_code' => $documentType->code,
                        'document_type_name' => $documentType->name,
                        'is_required' => $document->is_required,
                        'status_at_submission' => $this->enumValue($document->status_at_submission),
                        'version_number' => $currentVersion?->version_number,
                        'original_filename' => $currentVersion?->original_filename,
                        'mime_type' => $currentVersion?->mime_type,
                        'checksum' => $currentVersion?->checksum,
                    ];
                })
                ->values()
                ->all(),
            ApplicationSnapshotType::Summary->value => [
                'application_number' => $application->application_number,
                'contest_code' => $application->contest->code,
                'contest_title' => $application->contest->title,
                'program_name' => $application->program->name,
                'submitted_at' => $this->dateTime($application->submitted_at)?->toIso8601String(),
                'member_count' => $householdMembers->count(),
                'monthly_income' => $household->totalMonthlyIncome(),
                'annual_income' => $household->totalAnnualIncome(),
                'document_count' => $applicationDocuments->count(),
            ],
        ];

        foreach ($snapshots as $type => $data) {
            $application->snapshots()->updateOrCreate(
                ['snapshot_type' => $type],
                ['data' => $data],
            );
        }

        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $application,
            module: 'applications',
            action: 'snapshot',
            description: 'Snapshots da candidatura criados.',
            metadata: ['snapshot_types' => array_keys($snapshots)],
        );
    }

    private function enumValue(mixed $value): string|int|null
    {
        return $value instanceof BackedEnum ? $value->value : null;
    }

    private function dateTime(mixed $value): ?CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : null;
    }
}
