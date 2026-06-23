<?php

namespace App\Services\Reporting;

use App\Models\IndicatorDefinition;
use App\Services\Reporting\Indicators\AllocationIndicatorsService;
use App\Services\Reporting\Indicators\ApplicationIndicatorsService;
use App\Services\Reporting\Indicators\CommunicationIndicatorsService;
use App\Services\Reporting\Indicators\ComplaintIndicatorsService;
use App\Services\Reporting\Indicators\DocumentIndicatorsService;
use App\Services\Reporting\Indicators\FinanceIndicatorsService;
use App\Services\Reporting\Indicators\HousingIndicatorsService;
use App\Services\Reporting\Indicators\MaintenanceIndicatorsService;
use InvalidArgumentException;

class IndicatorRegistry
{
    private const ALLOWED = [
        ApplicationIndicatorsService::class => ['countApplicationsByContest', 'countApplicationsByProgram', 'countApplicationsByStatus', 'countSubmittedApplications', 'countEligibleApplications', 'countExcludedApplications', 'averageAnalysisTime', 'averageSubmissionToDecisionTime'],
        DocumentIndicatorsService::class => ['countPendingDocuments', 'countSubmittedDocuments', 'countRejectedDocuments', 'countValidatedDocuments', 'countExpiredDocuments', 'averageValidationTime', 'countIncompleteApplications'],
        ComplaintIndicatorsService::class => ['countSubmittedComplaints', 'countComplaintsUnderReview', 'countAcceptedComplaints', 'countRejectedComplaints', 'countPartiallyAcceptedComplaints', 'averageComplaintDecisionTime', 'countPendingHearings'],
        HousingIndicatorsService::class => ['countAvailableHousingUnits', 'countAllocatedHousingUnits', 'countContractedHousingUnits', 'countOccupiedHousingUnits', 'countHousingUnitsUnderMaintenance', 'occupancyRate'],
        AllocationIndicatorsService::class => ['countAllocations', 'countAcceptedAllocations', 'countRefusedAllocations', 'countPendingAllocationResponses', 'countActiveReserveEntries', 'allocationRate'],
        FinanceIndicatorsService::class => ['totalIssuedRent', 'totalPaidRent', 'totalOverdueRent', 'countContractsInArrears', 'averageDaysOverdue', 'countActiveAgreements', 'countBreachedAgreements', 'countPendingRentReviews'],
        MaintenanceIndicatorsService::class => ['countRequestsByStatus', 'countPendingRequests', 'averageResolutionTime', 'totalMaintenanceCosts', 'costsByProperty', 'requestsByCategory', 'propertiesWithMostOccurrences', 'countScheduledInspections', 'countCompletedInspections'],
        CommunicationIndicatorsService::class => ['countSentCommunications', 'countFailedCommunications', 'countUnreadNotifications', 'communicationsByEvent', 'countPendingAcknowledgements'],
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function calculate(IndicatorDefinition $definition, array $filters): mixed
    {
        $methods = self::ALLOWED[$definition->calculation_service] ?? [];
        if (! in_array($definition->calculation_method, $methods, true)) {
            throw new InvalidArgumentException('O indicador não referencia um cálculo permitido.');
        }

        return app($definition->calculation_service)->{$definition->calculation_method}($filters);
    }

    /**
     * @return array<class-string, list<string>>
     */
    public function services(): array
    {
        return self::ALLOWED;
    }
}
