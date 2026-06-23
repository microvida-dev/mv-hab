<?php

namespace Database\Seeders;

use App\Enums\RetentionAction;
use App\Models\RetentionPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class RetentionPolicySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();

        foreach ($this->policies() as $policy) {
            RetentionPolicy::query()->updateOrCreate(
                ['code' => $policy['code']],
                [
                    ...$policy,
                    'created_by' => $admin?->id,
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     status: string,
     *     entity_type: class-string|string,
     *     retention_period_months: int,
     *     retention_action: string,
     *     legal_basis: string,
     *     requires_manual_approval: bool
     * }>
     */
    private function policies(): array
    {
        return [
            [
                'code' => 'rgpd_requests_5y',
                'name' => 'Pedidos RGPD — 5 anos',
                'description' => 'Retenção de pedidos dos titulares e histórico de resposta.',
                'status' => 'active',
                'entity_type' => 'App\\Models\\DataSubjectRequest',
                'retention_period_months' => 60,
                'retention_action' => RetentionAction::ReviewManually->value,
                'legal_basis' => 'Conservação para prova de cumprimento RGPD e auditoria municipal.',
                'requires_manual_approval' => true,
            ],
            [
                'code' => 'access_logs_2y',
                'name' => 'Logs de acesso — 2 anos',
                'description' => 'Retenção de logs técnicos e administrativos de acesso.',
                'status' => 'active',
                'entity_type' => 'App\\Models\\AccessLog',
                'retention_period_months' => 24,
                'retention_action' => RetentionAction::Archive->value,
                'legal_basis' => 'Segurança, deteção de incidentes e auditoria.',
                'requires_manual_approval' => true,
            ],
            [
                'code' => 'data_exports_14d',
                'name' => 'Exportações RGPD — 14 dias',
                'description' => 'Pacotes de exportação devem expirar e ser revistos em prazo curto.',
                'status' => 'active',
                'entity_type' => 'App\\Models\\DataExportPackage',
                'retention_period_months' => 1,
                'retention_action' => RetentionAction::DeletePermanently->value,
                'legal_basis' => 'Minimização e limitação da conservação.',
                'requires_manual_approval' => true,
            ],
        ];
    }
}
