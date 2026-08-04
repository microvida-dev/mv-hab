#!/usr/bin/env bash
set -Eeuo pipefail
IFS=$'\n\t'

ROOT="$(git rev-parse --show-toplevel 2>/dev/null || true)"
[[ -n "$ROOT" ]] || { echo 'Execute dentro do repositório.' >&2; exit 2; }
cd "$ROOT"

PHP_FILES=(
  app/Console/Commands/OnboardMunicipality.php
  app/Console/Commands/ProvisionInitialMunicipalityCatalog.php
  app/Data/Municipalities
  app/Enums/MunicipalAdministratorInvitationStatus.php
  app/Enums/MunicipalityOnboardingConflict.php
  app/Enums/MunicipalityOnboardingStatus.php
  app/Jobs/SendMunicipalAdministratorInvitation.php
  app/Listeners/MarkMunicipalAdministratorInvitationConsumed.php
  app/Models/MunicipalAdministratorInvitation.php
  app/Models/Municipality.php
  app/Models/MunicipalityOnboardingRun.php
  app/Models/User.php
  app/Notifications/MunicipalAdministratorInvitationNotification.php
  app/Providers/AppServiceProvider.php
  app/Services/Access/MunicipalRoleTemplateRegistry.php
  app/Services/Municipalities/AlcanenaInitialCatalogService.php
  app/Services/Municipalities/MunicipalAdministratorInvitationService.php
  app/Services/Municipalities/MunicipalAdministratorRoleProvisioningService.php
  app/Services/Municipalities/MunicipalityIdentityNormalizer.php
  app/Services/Municipalities/MunicipalityOnboardingPlanner.php
  app/Services/Municipalities/MunicipalityOnboardingService.php
  app/Services/Municipalities/PlatformMunicipalRoleAssignmentService.php
  database/migrations/2026_08_04_000056_add_unique_contact_email_to_municipalities.php
  database/migrations/2026_08_04_000057_create_municipality_onboarding_tables.php
  tests/Feature/Municipalities
  tests/Feature/Sprint22CandidateSupportTest.php
  tests/Feature/Security/VisitOperationalMunicipalScopeTest.php
  tests/Feature/UX/Concerns/CreatesAnalyticsFixtures.php
  tests/Feature/UX/DashboardAccessibilityTest.php
  tests/Feature/UX/UniversalSearchTest.php
  tests/Unit/Municipalities
)

echo '== PHP lint =='
while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
done < <(find "${PHP_FILES[@]}" -type f -name '*.php' -print0)
echo 'PHP_LINT=PASS'

echo '== Testes dirigidos =='
php artisan test \
  tests/Unit/Municipalities \
  tests/Feature/Municipalities \
  tests/Feature/Sprint22CandidateSupportTest.php \
  tests/Feature/Security/VisitOperationalMunicipalScopeTest.php \
  tests/Feature/UX/DashboardAccessibilityTest.php \
  tests/Feature/UX/UniversalSearchTest.php \
  --no-ansi

echo '== PHPStan dirigido =='
vendor/bin/phpstan analyse --no-progress --memory-limit=1G \
  app/Services/Municipalities \
  app/Data/Municipalities \
  app/Console/Commands/OnboardMunicipality.php \
  app/Console/Commands/ProvisionInitialMunicipalityCatalog.php \
  app/Models/Municipality.php \
  app/Models/User.php \
  app/Models/MunicipalityOnboardingRun.php \
  app/Models/MunicipalAdministratorInvitation.php \
  app/Jobs/SendMunicipalAdministratorInvitation.php \
  app/Notifications/MunicipalAdministratorInvitationNotification.php \
  app/Listeners/MarkMunicipalAdministratorInvitationConsumed.php \
  app/Providers/AppServiceProvider.php \
  app/Services/Access/MunicipalRoleTemplateRegistry.php \
  tests/Unit/Municipalities \
  tests/Feature/Municipalities \
  tests/Feature/Sprint22CandidateSupportTest.php \
  tests/Feature/Security/VisitOperationalMunicipalScopeTest.php \
  tests/Feature/UX/Concerns/CreatesAnalyticsFixtures.php \
  tests/Feature/UX/DashboardAccessibilityTest.php \
  tests/Feature/UX/UniversalSearchTest.php

echo '== Pint dirigido =='
vendor/bin/pint --test "${PHP_FILES[@]}"

echo '== Contratos e build =='
composer validate --strict
composer audit --locked
npm run build

echo '== Integridade Git =='
git diff --check

echo 'MUNICIPALITY_ONBOARDING_DIRECTED_VALIDATION=PASS'
