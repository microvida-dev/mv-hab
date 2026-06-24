<?php

namespace Database\Seeders;

use App\Models\MunicipalTeam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MunicipalTeamSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Gabinete Técnico', 'description' => 'Análise técnica e tramitação administrativa.', 'functional_scopes' => ['applications', 'documents', 'eligibility']],
            ['name' => 'Gabinete Jurídico', 'description' => 'Contratos, reclamações, audiência prévia e pareceres.', 'functional_scopes' => ['contracts', 'complaints', 'hearings']],
            ['name' => 'Gabinete Financeiro', 'description' => 'Rendas, cobranças e reporting financeiro.', 'functional_scopes' => ['finance', 'payments', 'reports']],
            ['name' => 'Gabinete de Habitação', 'description' => 'Gestão habitacional, ocupação e atribuições.', 'functional_scopes' => ['housing_units', 'allocations', 'contracts']],
            ['name' => 'Manutenção', 'description' => 'Pedidos e intervenções de manutenção.', 'functional_scopes' => ['maintenance_requests']],
            ['name' => 'Vistorias', 'description' => 'Vistorias, autos e evidências técnicas.', 'functional_scopes' => ['inspections']],
            ['name' => 'Atendimento', 'description' => 'Apoio ao cidadão e comunicações operacionais.', 'functional_scopes' => ['support', 'candidate_experience']],
            ['name' => 'Auditoria', 'description' => 'Auditoria interna, RGPD e controlo de acessos.', 'functional_scopes' => ['audit_logs', 'privacy', 'access_audit']],
        ])->each(function (array $team): void {
            MunicipalTeam::query()->updateOrCreate(
                ['slug' => Str::slug($team['name'])],
                [
                    'name' => $team['name'],
                    'description' => $team['description'],
                    'status' => 'active',
                    'functional_scopes' => $team['functional_scopes'],
                ],
            );
        });
    }
}
