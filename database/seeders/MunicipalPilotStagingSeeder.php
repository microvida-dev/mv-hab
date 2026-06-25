<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\MunicipalTeam;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MunicipalPilotStagingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemAccessSeeder::class,
            MunicipalTeamSeeder::class,
            SecurityRgpdSeeder::class,
            SimulatorConfigurationSeeder::class,
            DemoAlcanenaAffordableRentSeeder::class,
        ]);

        $municipality = Municipality::query()
            ->where('code', DemoAlcanenaAffordableRentSeeder::MUNICIPALITY_CODE)
            ->first();

        if (! $municipality instanceof Municipality) {
            return;
        }

        $this->seedOperationalUsers($municipality);
        $this->call(CandidateSupportDemoSeeder::class);
    }

    private function seedOperationalUsers(Municipality $municipality): void
    {
        /** @var list<array{0: string, 1: string, 2: string, 3: list<string>}> $users */
        $users = [
            ['Atendimento Demo Alcanena', 'atendimento-demo@exemplo.pt', 'support_agent', ['Atendimento']],
            ['Jurídico Demo Alcanena', 'juridico-demo@exemplo.pt', 'legal_manager', ['Gabinete Jurídico']],
            ['Financeiro Demo Alcanena', 'financeiro-demo@exemplo.pt', 'financial_manager', ['Gabinete Financeiro']],
            ['Habitação Demo Alcanena', 'habitacao-demo@exemplo.pt', 'housing_manager', ['Gabinete de Habitação']],
            ['Vistorias Demo Alcanena', 'vistorias-demo@exemplo.pt', 'inspection_manager', ['Vistorias']],
            ['Auditoria Demo Alcanena', 'auditoria-demo@exemplo.pt', 'auditor', ['Auditoria']],
        ];

        foreach ($users as [$name, $email, $role, $teams]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'municipality_id' => $municipality->id,
                    'name' => $name,
                    'password' => Hash::make(hash('sha256', $email.'|mvhab-pilot-demo-access-disabled')),
                    'email_verified_at' => CarbonImmutable::create(2026, 1, 1, 0),
                    'status' => 'active',
                    'mfa_required' => $role !== 'support_agent',
                    'internal_notes' => 'Utilizador fictício para piloto municipal controlado. Acesso real deve ser criado por convite/reset seguro.',
                ],
            );

            $user->assignRole($role);

            foreach ($teams as $teamName) {
                $team = MunicipalTeam::query()->where('name', $teamName)->first();

                if (! $team instanceof MunicipalTeam) {
                    continue;
                }

                $user->municipalTeams()->syncWithoutDetaching([
                    $team->id => [
                        'role_in_team' => 'member',
                        'joined_at' => now(),
                    ],
                ]);
            }
        }
    }
}
