<?php

namespace Database\Seeders\Pilot;

use Illuminate\Database\Seeder;

class PilotDocumentWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        app(PilotScenarioBuilder::class)->ensureDocumentWorkflow();
    }
}
