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

        /*
         * Os seeders especializados serão adicionados
         * incrementalmente nas próximas fases da Sprint 51.
         */
    }
}
