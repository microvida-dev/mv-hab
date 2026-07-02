<?php

namespace Database\Seeders\Pilot;

use Database\Seeders\AdministrativeWorkflowConfigSeeder;
use Database\Seeders\DashboardDefinitionSeeder;
use Database\Seeders\DashboardWidgetSeeder;
use Database\Seeders\DocumentTemplateSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\EligibilityBaseCriteriaSeeder;
use Database\Seeders\IndicatorDefinitionSeeder;
use Database\Seeders\InspectionChecklistTemplateSeeder;
use Database\Seeders\MaintenanceCategorySeeder;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\NotificationEventRuleSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\RequiredDocumentSeeder;
use Database\Seeders\RetentionPolicySeeder;
use Database\Seeders\ScoringBaseCriteriaSeeder;
use Database\Seeders\SecurityRgpdSeeder;
use Database\Seeders\SimulatorConfigurationSeeder;
use Database\Seeders\SystemAccessSeeder;
use Database\Seeders\TemplateVariableSeeder;
use Illuminate\Database\Seeder;

class PilotCoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemAccessSeeder::class,
            MunicipalTeamSeeder::class,
            SecurityRgpdSeeder::class,
            SimulatorConfigurationSeeder::class,
            DocumentTypeSeeder::class,
            RequiredDocumentSeeder::class,
            EligibilityBaseCriteriaSeeder::class,
            ScoringBaseCriteriaSeeder::class,
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
            RetentionPolicySeeder::class,
            AdministrativeWorkflowConfigSeeder::class,
        ]);
    }
}
