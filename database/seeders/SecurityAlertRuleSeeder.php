<?php

namespace Database\Seeders;

use App\Enums\SecurityAlertSeverity;
use App\Models\SecurityAlertRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class SecurityAlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();

        foreach ($this->rules() as $rule) {
            SecurityAlertRule::query()->updateOrCreate(
                ['code' => $rule['code']],
                [
                    ...$rule,
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     description: string,
     *     event_code: string,
     *     severity: string,
     *     threshold: int,
     *     window_minutes: int,
     *     is_active: bool
     * }>
     */
    private function rules(): array
    {
        return [
            [
                'code' => 'multiple_failed_logins',
                'name' => 'Múltiplas falhas de login',
                'description' => 'Sinaliza tentativas repetidas de autenticação falhada no intervalo configurado.',
                'event_code' => 'auth.failed_login',
                'severity' => SecurityAlertSeverity::High->value,
                'threshold' => 3,
                'window_minutes' => 15,
                'is_active' => true,
            ],
            [
                'code' => 'sensitive_document_bulk_download',
                'name' => 'Downloads documentais sensíveis',
                'description' => 'Sinaliza volume elevado de downloads de documentos sensíveis.',
                'event_code' => 'documents.download',
                'severity' => SecurityAlertSeverity::Critical->value,
                'threshold' => 10,
                'window_minutes' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'sensitive_report_bulk_export',
                'name' => 'Exportações sensíveis',
                'description' => 'Sinaliza exportações repetidas de dados sensíveis.',
                'event_code' => 'reports.export',
                'severity' => SecurityAlertSeverity::High->value,
                'threshold' => 5,
                'window_minutes' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'access_to_multiple_candidate_records',
                'name' => 'Acesso a múltiplos titulares',
                'description' => 'Sinaliza acessos sucessivos a dados de titulares distintos.',
                'event_code' => 'records.view',
                'severity' => SecurityAlertSeverity::Medium->value,
                'threshold' => 20,
                'window_minutes' => 60,
                'is_active' => true,
            ],
        ];
    }
}
