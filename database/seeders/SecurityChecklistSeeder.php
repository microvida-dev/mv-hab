<?php

namespace Database\Seeders;

use App\Models\SecurityChecklist;
use App\Models\User;
use App\Services\Security\PreProductionSecurityChecklistService;
use Illuminate\Database\Seeder;

class SecurityChecklistSeeder extends Seeder
{
    public function run(): void
    {
        if (SecurityChecklist::query()->where('environment', 'pre-production')->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'admin@example.com')->first();

        if (! $admin) {
            return;
        }

        app(PreProductionSecurityChecklistService::class)->create($admin, 'pre-production');
    }
}
