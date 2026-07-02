<?php

namespace Database\Seeders;

use Database\Seeders\Pilot\PilotApplicationStatesSeeder;
use Database\Seeders\Pilot\PilotCandidateJourneySeeder;
use Database\Seeders\Pilot\PilotContractsTenantSeeder;
use Database\Seeders\Pilot\PilotCoreSeeder;
use Database\Seeders\Pilot\PilotDocumentWorkflowSeeder;
use Database\Seeders\Pilot\PilotHearingComplaintSeeder;
use Database\Seeders\Pilot\PilotHousingUnitsSeeder;
use Database\Seeders\Pilot\PilotMaintenanceInspectionSeeder;
use Database\Seeders\Pilot\PilotOperationsAgendaSeeder;
use Database\Seeders\Pilot\PilotProgramsContestsSeeder;
use Database\Seeders\Pilot\PilotRankingAndAllocationSeeder;
use Database\Seeders\Pilot\PilotRgpdAuditSeeder;
use Database\Seeders\Pilot\PilotUsersAndTeamsSeeder;
use Database\Seeders\Pilot\PilotVisitsSupportSeeder;
use Illuminate\Database\Seeder;

class MunicipalStateOfArtSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PilotCoreSeeder::class,
            PilotUsersAndTeamsSeeder::class,
            PilotProgramsContestsSeeder::class,
            PilotHousingUnitsSeeder::class,
            PilotCandidateJourneySeeder::class,
            PilotApplicationStatesSeeder::class,
            PilotDocumentWorkflowSeeder::class,
            PilotRankingAndAllocationSeeder::class,
            PilotHearingComplaintSeeder::class,
            PilotContractsTenantSeeder::class,
            PilotMaintenanceInspectionSeeder::class,
            PilotVisitsSupportSeeder::class,
            PilotOperationsAgendaSeeder::class,
            PilotRgpdAuditSeeder::class,
        ]);
    }
}
