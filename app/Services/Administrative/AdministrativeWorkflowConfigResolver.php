<?php

namespace App\Services\Administrative;

use App\Models\AdministrativeWorkflowConfig;
use App\Models\Application;

class AdministrativeWorkflowConfigResolver
{
    public function resolveForApplication(Application $application): AdministrativeWorkflowConfig
    {
        $config = AdministrativeWorkflowConfig::query()
            ->where('contest_id', $application->contest_id)
            ->where('is_active', true)
            ->latest()
            ->first()
            ?? AdministrativeWorkflowConfig::query()
                ->where('program_id', $application->program_id)
                ->whereNull('contest_id')
                ->where('is_active', true)
                ->latest()
                ->first();

        if ($config instanceof AdministrativeWorkflowConfig) {
            return $config;
        }

        $fallback = new AdministrativeWorkflowConfig;
        $fallback->forceFill([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'name' => 'Fallback conservador pendente de validação jurídica',
            'is_active' => true,
            'default_correction_deadline_days' => 10,
            'allow_deadline_extension' => false,
            'max_deadline_extensions' => 0,
            'auto_mark_overdue' => false,
            'requires_decision_approval' => false,
        ]);

        return $fallback;
    }
}
