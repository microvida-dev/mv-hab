<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTaskSlaPolicy;
use App\Services\Workflows\WorkTaskSlaService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MunicipalEndToEndWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SystemAccessSeeder::class,
            MunicipalTeamSeeder::class,
        ]);

        $municipality = $this->municipality();
        $password = $this->manualTestPassword();
        $users = $this->seedWorkflowUsers($municipality, $password);

        $this->assignTeamManagers($users);
        $this->seedSlaPolicies();

        if ($password === null && $this->command !== null) {
            $this->command->warn('Utilizadores E2E criados sem password conhecida. Defina MVHAB_E2E_USER_PASSWORD localmente ou use reset seguro.');
        }
    }

    private function municipality(): Municipality
    {
        return Municipality::query()->firstOrCreate(
            ['code' => DemoAlcanenaAffordableRentSeeder::MUNICIPALITY_CODE],
            [
                'name' => 'Município de Alcanena',
                'tax_number' => null,
                'contact_email' => 'municipio-demo@example.test',
                'settings' => [
                    'purpose' => 'Dados fictícios para teste end-to-end municipal.',
                    'external_integrations' => 'Out of scope by municipal decision.',
                ],
                'active' => true,
            ],
        );
    }

    private function manualTestPassword(): ?string
    {
        $password = config('mvhab.e2e_user_password');

        if (! is_string($password) || trim($password) === '') {
            return null;
        }

        return trim($password);
    }

    /**
     * @return array<string, User>
     */
    private function seedWorkflowUsers(Municipality $municipality, ?string $password): array
    {
        $users = [];

        foreach ($this->workflowUsers() as $definition) {
            $user = User::query()->firstOrNew(['email' => $definition['email']]);
            $knownPassword = $password ?? ($user->exists ? null : Str::random(64));

            $user->forceFill([
                'municipality_id' => $municipality->id,
                'name' => $definition['name'],
                'email_verified_at' => CarbonImmutable::create(2026, 1, 1, 0),
                'status' => 'active',
                'mfa_required' => false,
                'internal_notes' => 'Utilizador fictício para teste end-to-end MV HAB. Não usar em produção municipal.',
            ]);

            if ($knownPassword !== null) {
                $user->forceFill(['password' => Hash::make($knownPassword)]);
            }

            $user->save();
            $user->assignRole($definition['role']);

            foreach ($definition['teams'] as $teamName => $roleInTeam) {
                $team = MunicipalTeam::query()->where('name', $teamName)->first();

                if (! $team instanceof MunicipalTeam) {
                    continue;
                }

                $user->municipalTeams()->syncWithoutDetaching([
                    $team->id => [
                        'role_in_team' => $roleInTeam,
                        'joined_at' => now(),
                    ],
                ]);
            }

            $users[$definition['key']] = $user;
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $users
     */
    private function assignTeamManagers(array $users): void
    {
        $managers = [
            'Gabinete Técnico' => $users['technical'] ?? null,
            'Atendimento' => $users['support'] ?? null,
            'Gabinete Jurídico' => $users['legal'] ?? null,
            'Gabinete Financeiro' => $users['financial'] ?? null,
            'Gabinete de Habitação' => $users['housing'] ?? null,
            'Manutenção' => $users['maintenance'] ?? null,
            'Vistorias' => $users['inspection'] ?? null,
            'Auditoria' => $users['auditor'] ?? null,
        ];

        foreach ($managers as $teamName => $manager) {
            if (! $manager instanceof User) {
                continue;
            }

            MunicipalTeam::query()
                ->where('name', $teamName)
                ->update(['manager_user_id' => $manager->id]);
        }
    }

    private function seedSlaPolicies(): void
    {
        foreach (app(WorkTaskSlaService::class)->defaultPolicies() as $type => $policy) {
            $model = WorkTaskSlaPolicy::withTrashed()->firstOrNew(['type' => $type]);

            $model->forceFill([
                'label' => $policy['label'],
                'business_days' => $policy['business_days'],
                'warning_business_days' => $policy['warning_business_days'],
                'is_active' => true,
                'deleted_at' => null,
            ])->save();
        }
    }

    /**
     * @return list<array{key: string, name: string, email: string, role: string, teams: array<string, string>}>
     */
    private function workflowUsers(): array
    {
        return [
            [
                'key' => 'admin',
                'name' => 'E2E Administrador Municipal',
                'email' => 'e2e.admin@example.test',
                'role' => 'administrator',
                'teams' => ['Auditoria' => 'coordenação de teste'],
            ],
            [
                'key' => 'candidate',
                'name' => 'E2E Candidato Municipal',
                'email' => 'e2e.candidato@example.test',
                'role' => 'candidate',
                'teams' => [],
            ],
            [
                'key' => 'support',
                'name' => 'E2E Atendimento Municipal',
                'email' => 'e2e.atendimento@example.test',
                'role' => 'support_agent',
                'teams' => ['Atendimento' => 'atendimento e apoio ao candidato'],
            ],
            [
                'key' => 'technical',
                'name' => 'E2E Técnico Municipal',
                'email' => 'e2e.tecnico@example.test',
                'role' => 'municipal_technician',
                'teams' => ['Gabinete Técnico' => 'receção, documentos e elegibilidade'],
            ],
            [
                'key' => 'jury',
                'name' => 'E2E Júri Municipal',
                'email' => 'e2e.juri@example.test',
                'role' => 'jury',
                'teams' => [
                    'Gabinete Técnico' => 'júri de classificação',
                    'Gabinete Jurídico' => 'júri de reclamações',
                ],
            ],
            [
                'key' => 'legal',
                'name' => 'E2E Gestor Jurídico',
                'email' => 'e2e.juridico@example.test',
                'role' => 'legal_manager',
                'teams' => ['Gabinete Jurídico' => 'contratos, audiência e reclamações'],
            ],
            [
                'key' => 'housing',
                'name' => 'E2E Gestor de Habitação',
                'email' => 'e2e.habitacao@example.test',
                'role' => 'housing_manager',
                'teams' => ['Gabinete de Habitação' => 'património, visitas e atribuição'],
            ],
            [
                'key' => 'financial',
                'name' => 'E2E Gestor Financeiro',
                'email' => 'e2e.financeiro@example.test',
                'role' => 'financial_manager',
                'teams' => ['Gabinete Financeiro' => 'rendas e registos financeiros manuais'],
            ],
            [
                'key' => 'maintenance',
                'name' => 'E2E Gestor de Manutenção',
                'email' => 'e2e.manutencao@example.test',
                'role' => 'maintenance_manager',
                'teams' => ['Manutenção' => 'triagem e intervenção'],
            ],
            [
                'key' => 'inspection',
                'name' => 'E2E Gestor de Vistorias',
                'email' => 'e2e.vistorias@example.test',
                'role' => 'inspection_manager',
                'teams' => ['Vistorias' => 'agendamento e autos'],
            ],
            [
                'key' => 'auditor',
                'name' => 'E2E Auditor RGPD',
                'email' => 'e2e.auditor@example.test',
                'role' => 'auditor',
                'teams' => ['Auditoria' => 'auditoria e RGPD'],
            ],
        ];
    }
}
