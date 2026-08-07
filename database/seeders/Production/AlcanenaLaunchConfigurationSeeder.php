<?php

namespace Database\Seeders\Production;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AlcanenaLaunchConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                AlcanenaProductionSeeder::class,
                AlcanenaRegulatoryProfileSeeder::class,
                AlcanenaRequiredDocumentsSeeder::class,
                AlcanenaRgpdSeeder::class,
            ]);
        }, 3);

        $this->command->info('Configuração de lançamento de Alcanena concluída sem publicar programa ou concurso.');
    }
}
