<?php

namespace App\Services\DataReuse;

use App\Enums\ApplicationStatus;
use App\Enums\DataReuseStatus;
use App\Models\Application;
use App\Models\CandidateDataReuseProfile;
use App\Models\User;

class DataReuseEligibilityService
{
    public function statusFor(User $user, ?CandidateDataReuseProfile $profile, ?Application $targetApplication): DataReuseStatus
    {
        if ($profile === null || $profile->user_id !== $user->id) {
            return DataReuseStatus::Blocked;
        }

        if ($profile->expires_at !== null && $profile->expires_at->isPast()) {
            return DataReuseStatus::Expired;
        }

        if ($targetApplication !== null && $targetApplication->status !== ApplicationStatus::Draft) {
            return DataReuseStatus::Blocked;
        }

        if ($profile->last_confirmed_at === null || $profile->last_confirmed_at->lt(now()->subMonths(6))) {
            return DataReuseStatus::RequiresConfirmation;
        }

        return DataReuseStatus::Available;
    }

    /**
     * @return list<string>
     */
    public function warningsFor(?CandidateDataReuseProfile $profile): array
    {
        if ($profile === null) {
            return ['Não existe perfil de dados reutilizáveis disponível.'];
        }

        $warnings = ['Os documentos não são copiados automaticamente como válidos.'];

        if ($profile->last_confirmed_at === null) {
            $warnings[] = 'Os dados ainda não foram confirmados pelo candidato.';
        } elseif ($profile->last_confirmed_at->lt(now()->subMonths(6))) {
            $warnings[] = 'Os dados foram confirmados há mais de seis meses e devem ser revistos.';
        }

        if ($profile->expires_at !== null && $profile->expires_at->isPast()) {
            $warnings[] = 'O perfil de reutilização encontra-se expirado.';
        }

        return $warnings;
    }
}
