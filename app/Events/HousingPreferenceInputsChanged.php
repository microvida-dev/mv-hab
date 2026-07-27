<?php

namespace App\Events;

use App\Models\AdhesionRegistration;
use App\Models\Household;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class HousingPreferenceInputsChanged
{
    use Dispatchable, SerializesModels;

    public const COMPOSITION = 'household_composition';

    public const INCOME = 'household_income';

    public const HOUSING = 'current_housing';

    public const REGISTRATION = 'adhesion_registration';

    public const CORRECTION = 'correction_response';

    public const ANNUAL_UPDATE = 'annual_document_update';

    public function __construct(
        public readonly Household|AdhesionRegistration $subject,
        public readonly string $reason,
        public readonly string $category,
    ) {}
}
