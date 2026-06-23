<?php

namespace Database\Seeders;

use App\Enums\InternalAlertSeverity;
use App\Enums\InternalAlertStatus;
use App\Enums\InternalAlertType;
use App\Enums\ProcedureTemplateStatus;
use App\Enums\ProcedureTemplateType;
use App\Models\Contest;
use App\Models\InternalAlert;
use App\Models\ProcedureTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class Sprint24BackofficeOperationalSeeder extends Seeder
{
    public function run(): void
    {
        if (! Role::query()->where('name', 'administrator')->exists()) {
            $this->call(SystemAccessSeeder::class);
        }

        $administrator = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
            ->first();

        if (! $administrator instanceof User) {
            return;
        }

        foreach ($this->templates() as $template) {
            ProcedureTemplate::query()->updateOrCreate(
                [
                    'type' => $template['type']->value,
                    'name' => $template['name'],
                ],
                [
                    'template_number' => $template['template_number'],
                    'status' => ProcedureTemplateStatus::Active,
                    'description' => $template['description'],
                    'version' => 1,
                    'content' => $template['content'],
                    'variables' => $template['variables'],
                    'published_at' => now(),
                    'published_by' => $administrator->id,
                    'created_by' => $administrator->id,
                ],
            );
        }

        $contest = Contest::query()->latest('id')->first();

        InternalAlert::query()->updateOrCreate(
            ['alert_number' => 'ALT-DEMO-SPRINT24-001'],
            [
                'type' => InternalAlertType::MinuteReviewPending,
                'severity' => InternalAlertSeverity::Warning,
                'status' => InternalAlertStatus::Open,
                'title' => 'Ata de procedimento pendente de revisão',
                'message' => 'Alerta fictício para validar o painel operacional da Sprint 24.',
                'assigned_to' => $administrator->id,
                'assigned_role' => 'administrator',
                'contest_id' => $contest?->id,
                'program_id' => $contest?->program_id,
                'due_at' => now()->addDays(5),
                'metadata' => [
                    'demo' => true,
                    'sprint' => 24,
                    'note' => 'Dados fictícios sem informação pessoal real.',
                ],
                'created_by' => $administrator->id,
            ],
        );
    }

    /**
     * @return list<array{
     *     template_number:string,
     *     type:ProcedureTemplateType,
     *     name:string,
     *     description:string,
     *     content:string,
     *     variables:list<string>
     * }>
     */
    private function templates(): array
    {
        return [
            [
                'template_number' => 'MIN-DEMO-SPRINT24-001',
                'type' => ProcedureTemplateType::ApplicationReport,
                'name' => 'Relatório operacional de candidatura',
                'description' => 'Minuta demo para síntese administrativa de uma candidatura.',
                'content' => '<h1>Relatório operacional</h1><p>Candidatura: {{application_number}}</p><p>Concurso: {{contest_title}}</p><p>Emitido em: {{generated_at}}</p><p>Este documento foi gerado automaticamente com base nos dados registados na plataforma à data da emissão. A validação final compete aos serviços municipais.</p>',
                'variables' => ['application_number', 'contest_title', 'generated_at'],
            ],
            [
                'template_number' => 'MIN-DEMO-SPRINT24-002',
                'type' => ProcedureTemplateType::ProvisionalList,
                'name' => 'Lista provisória para validação',
                'description' => 'Minuta demo para revisão humana de lista provisória.',
                'content' => '<h1>Lista provisória</h1><p>Concurso: {{contest_title}}</p><p>A lista foi gerada com base nos critérios, estados e dados disponíveis na plataforma. Deve ser revista e validada pelos serviços competentes antes de publicação.</p>',
                'variables' => ['contest_title'],
            ],
            [
                'template_number' => 'MIN-DEMO-SPRINT24-003',
                'type' => ProcedureTemplateType::ProcedureMinute,
                'name' => 'Ata de acompanhamento do procedimento',
                'description' => 'Minuta demo para ata de reunião ou deliberação interna.',
                'content' => '<h1>Ata do procedimento</h1><p>Procedimento: {{process_number}}</p><p>Concurso: {{contest_title}}</p><p>A ata foi preparada automaticamente a partir dos dados do procedimento e deve ser revista, validada e aprovada pelos responsáveis competentes.</p>',
                'variables' => ['process_number', 'contest_title'],
            ],
            [
                'template_number' => 'MIN-DEMO-SPRINT24-004',
                'type' => ProcedureTemplateType::ProcessConfirmation,
                'name' => 'Confirmação de receção de processo',
                'description' => 'Minuta demo para confirmação de processo ao candidato.',
                'content' => '<h1>Confirmação de processo</h1><p>Foi registado o processo {{process_number}} relativo à candidatura {{application_number}}.</p>',
                'variables' => ['process_number', 'application_number'],
            ],
        ];
    }
}
