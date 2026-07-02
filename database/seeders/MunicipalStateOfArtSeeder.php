<?php

namespace Database\Seeders;

use App\Models\User;
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
use Illuminate\Support\Facades\Hash;

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

        $this->syncKnownDemoPassword();
    }

    private function syncKnownDemoPassword(): void
    {
        $password = config('mvhab.e2e_user_password');

        if (! is_string($password) || trim($password) === '') {
            return;
        }

        $passwordHash = Hash::make(trim($password));

        User::query()
            ->where(function ($query): void {
                $query
                    ->where('email', 'like', '%@example.test')
                    ->orWhere('email', 'like', '%@exemplo.pt');
            })
            ->eachById(function (User $user) use ($passwordHash): void {
                $user->forceFill(['password' => $passwordHash])->save();
            });
    }
}
