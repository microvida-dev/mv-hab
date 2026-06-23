<?php

namespace App\Services\DataReuse;

use App\Models\Application;
use App\Models\CandidateDataReuseProfile;

class DataReuseSnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public function fromProfile(CandidateDataReuseProfile $profile): array
    {
        return [
            'registration' => $profile->registration_snapshot,
            'household' => $profile->household_snapshot,
            'income' => $profile->income_snapshot,
            'housing' => $profile->housing_snapshot,
            'documents_summary' => $profile->documents_snapshot,
            'source' => [
                'profile_id' => $profile->id,
                'profile_number' => $profile->profile_number,
                'last_confirmed_at' => $profile->last_confirmed_at?->toISOString(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromApplication(Application $application): array
    {
        $application->loadMissing(['adhesionRegistration', 'household.members', 'currentHousingSituation', 'documentSubmissions']);

        return [
            'application' => [
                'id' => $application->id,
                'number' => $application->application_number,
                'submitted_at' => $application->submitted_at?->toISOString(),
            ],
            'registration_id' => $application->adhesion_registration_id,
            'household_id' => $application->household_id,
            'current_housing_situation_id' => $application->current_housing_situation_id,
            'documents_summary' => [
                'count' => $application->documentSubmissions->count(),
                'validity_not_copied' => true,
            ],
        ];
    }
}
