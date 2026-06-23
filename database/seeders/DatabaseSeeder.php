<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemAccessSeeder::class,
            UserSeeder::class,
            ProgramSeeder::class,
            IncomeSourceSeeder::class,
            DocumentTypeSeeder::class,
            RequiredDocumentSeeder::class,
            EligibilityBaseCriteriaSeeder::class,
            EligibilityDemoRuleSetSeeder::class,
            ScoringBaseCriteriaSeeder::class,
            ScoringDemoRuleSetSeeder::class,
            MaintenanceCategorySeeder::class,
            InspectionChecklistTemplateSeeder::class,
            TemplateVariableSeeder::class,
            NotificationTemplateSeeder::class,
            NotificationEventRuleSeeder::class,
            DocumentTemplateSeeder::class,
            IndicatorDefinitionSeeder::class,
            DashboardDefinitionSeeder::class,
            DashboardWidgetSeeder::class,
            ReportDefinitionSeeder::class,
            SecurityRgpdSeeder::class,
            SimulatorConfigurationSeeder::class,
            DemoAlcanenaAffordableRentSeeder::class,
            Sprint24BackofficeOperationalSeeder::class,
            HousingUnitSeeder::class,
            CitizenSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
