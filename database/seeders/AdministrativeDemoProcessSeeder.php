<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use App\Services\Administrative\ApplicationIntakeService;
use Illuminate\Database\Seeder;

class AdministrativeDemoProcessSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'municipal_technician'))->first();

        if ($actor === null) {
            return;
        }

        Application::query()
            ->where('status', ApplicationStatus::Submitted->value)
            ->whereDoesntHave('administrativeProcess')
            ->limit(3)
            ->get()
            ->each(fn (Application $application) => app(ApplicationIntakeService::class)->createProcess($application, $actor));
    }
}
