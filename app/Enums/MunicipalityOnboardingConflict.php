<?php

namespace App\Enums;

enum MunicipalityOnboardingConflict: string
{
    case MunicipalityCodeExists = 'municipality_code_exists';
    case MunicipalityTaxNumberExists = 'municipality_tax_number_exists';
    case MunicipalityContactEmailExists = 'municipality_contact_email_exists';
    case AdministratorEmailExists = 'administrator_email_exists';
    case OnboardingAlreadyCompleted = 'onboarding_already_completed';
    case OnboardingInProgress = 'onboarding_in_progress';
    case OnboardingFingerprintMismatch = 'onboarding_fingerprint_mismatch';
    case RoleIdentifierReserved = 'role_identifier_reserved';
}
