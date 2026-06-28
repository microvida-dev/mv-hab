<?php

namespace App\Services\Search;

use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\Sources\ApplicationSearchSource;
use App\Services\Search\Sources\CandidateSearchSource;
use App\Services\Search\Sources\CommandSearchSource;
use App\Services\Search\Sources\ContestSearchSource;
use App\Services\Search\Sources\ContractSearchSource;
use App\Services\Search\Sources\HousingUnitSearchSource;
use App\Services\Search\Sources\InspectionSearchSource;
use App\Services\Search\Sources\MaintenanceRequestSearchSource;
use App\Services\Search\Sources\ProgramSearchSource;
use App\Services\Search\Sources\ReportSearchSource;
use App\Services\Search\Sources\SupportTicketSearchSource;
use App\Services\Search\Sources\WorkspaceSearchSource;
use App\Services\Search\Sources\WorkTaskSearchSource;

class SearchSourceRegistry
{
    public function __construct(
        private readonly WorkspaceSearchSource $workspace,
        private readonly ApplicationSearchSource $applications,
        private readonly CandidateSearchSource $candidates,
        private readonly ContestSearchSource $contests,
        private readonly ProgramSearchSource $programs,
        private readonly HousingUnitSearchSource $housingUnits,
        private readonly ContractSearchSource $contracts,
        private readonly WorkTaskSearchSource $workTasks,
        private readonly SupportTicketSearchSource $supportTickets,
        private readonly MaintenanceRequestSearchSource $maintenanceRequests,
        private readonly InspectionSearchSource $inspections,
        private readonly ReportSearchSource $reports,
        private readonly CommandSearchSource $commands,
    ) {}

    /**
     * @return list<SearchSource>
     */
    public function sources(): array
    {
        return [
            $this->workspace,
            $this->applications,
            $this->candidates,
            $this->contests,
            $this->programs,
            $this->housingUnits,
            $this->contracts,
            $this->workTasks,
            $this->supportTickets,
            $this->maintenanceRequests,
            $this->inspections,
            $this->reports,
            $this->commands,
        ];
    }

    public function commands(): CommandSearchSource
    {
        return $this->commands;
    }
}
