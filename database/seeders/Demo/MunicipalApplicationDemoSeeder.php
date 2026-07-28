<?php

namespace Database\Seeders\Demo;

use App\Support\Demo\MunicipalApplicationDemoContext;
use Illuminate\Database\Seeder;

final class MunicipalApplicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        app(MunicipalApplicationDemoContext::class)
            ->assertSeederAllowed();

        $this->call([
            MunicipalApplicationDemoAccessSeeder::class,
            MunicipalApplicationDemoCatalogSeeder::class,
            MunicipalApplicationDemoCandidateSeeder::class,
            MunicipalApplicationDemoSubmissionSeeder::class,
        ]);
    }
}
