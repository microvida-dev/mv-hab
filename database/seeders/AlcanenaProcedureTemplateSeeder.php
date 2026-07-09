<?php

namespace Database\Seeders;

use App\Enums\ProcedureTemplateStatus;
use App\Enums\ProcedureTemplateType;
use App\Models\ProcedureTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AlcanenaProcedureTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $publisher = $this->publisher();

        foreach ($this->templates() as $templateData) {
            $template = ProcedureTemplate::withTrashed()->firstOrNew([
                'template_number' => $templateData['template_number'],
            ]);

            if ($template->trashed()) {
                $template->restore();
            }

            $template->forceFill([
                'type' => ProcedureTemplateType::ProcedureMinute,
                'status' => ProcedureTemplateStatus::Active,
                'name' => $templateData['name'],
                'description' => $templateData['description'],
                'version' => 1,
                'content' => '<p>Template oficial renderizado por AlcanenaAta01Renderer.</p>',
                'variables' => [],
                'published_at' => now(),
                'published_by' => $publisher->id,
                'created_by' => $template->created_by ?? $publisher->id,
                'updated_by' => $publisher->id,
            ])->save();
        }
    }

    private function publisher(): User
    {
        $publisher = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
            ->first();

        if ($publisher instanceof User) {
            return $publisher;
        }

        $publisher = User::query()->first();

        if ($publisher instanceof User) {
            return $publisher;
        }

        $publisher = User::query()->create([
            'name' => 'Sistema Procedimentos',
            'email' => 'procedimentos.alcanena@example.test',
            'password' => Hash::make(Str::random(64)),
            'email_verified_at' => now(),
        ]);

        $publisher->assignRole('administrator');

        return $publisher;
    }

    /**
     * @return list<array{template_number: string, name: string, description: string, content: string}>
     */
    private function templates(): array
    {
        return [
            $this->template('ALC-ATA-01-SERIACAO-INICIAL', 'Ata 01 — Seriação inicial', 'Minuta para seriação inicial das candidaturas.', 'seriação inicial das candidaturas'),
            $this->template('ALC-ATA-02-RELATORIO-PRELIMINAR', 'Ata 02 — Relatório preliminar', 'Minuta para relatório preliminar do procedimento.', 'relatório preliminar do procedimento'),
            $this->template('ALC-ATA-03-AUDIENCIA-PREVIA', 'Ata 03 — Audiência prévia', 'Minuta para fase de audiência prévia.', 'análise da audiência prévia'),
            $this->template('ALC-ATA-05-DELIBERACAO-SORTEIO', 'Ata 05 — Deliberação de sorteio', 'Minuta para deliberação sobre sorteio.', 'deliberação de realização de sorteio'),
            $this->template('ALC-ATA-06-SORTEIO-PUBLICO', 'Ata 06 — Sorteio público', 'Minuta para ato de sorteio público.', 'realização de sorteio público'),
            $this->template('ALC-ATA-07-RELATORIO-FINAL', 'Ata 07 — Relatório final', 'Minuta para relatório final do procedimento.', 'relatório final do procedimento'),
            $this->template('ALC-ATA-08-REAPRECIACAO-DESISTENCIA', 'Ata 08 — Reapreciação por desistência', 'Minuta para reapreciação decorrente de desistência.', 'reapreciação por desistência'),
            $this->template('ALC-ATA-09-NOVO-SORTEIO', 'Ata 09 — Novo sorteio', 'Minuta para novo sorteio.', 'preparação de novo sorteio'),
            $this->template('ALC-ATA-10-RELATORIO-FINAL-ATRIBUICAO', 'Ata 10 — Relatório final de atribuição', 'Minuta para relatório final de atribuição.', 'relatório final de atribuição'),
        ];
    }

    /**
     * @return array{template_number: string, name: string, description: string, content: string}
     */
    private function template(string $number, string $name, string $description, string $subject): array
    {
        return [
            'template_number' => $number,
            'name' => $name,
            'description' => $description,
            'content' => <<<HTML
<h1>{$name}</h1>
<p><strong>{{municipality_name}}</strong> — {{municipal_department}}</p>
<p>Registo: {{municipal_registry_number}} · Processo: {{municipal_process_number}} · Referência externa: {{external_reference}}</p>
<p>Aos {{meeting_date}}, pelas {{meeting_time}}, em {{meeting_location}}, reuniu o júri/serviços competentes para {$subject}, no âmbito do concurso {{contest_code}} — {{contest_title}}.</p>
<p>Estado do concurso: {{contest_status}}. Candidaturas: {{contest_applications_total}}. Habitações: {{contest_housing_units_total}}.</p>
<p>Membros do júri: {{jury_members}}</p>
<p>Habitações associadas: {{housing_units_summary}}</p>
<p>Candidaturas: {{applications_summary}}</p>
<p>Lista provisória: {{provisional_list_summary}}</p>
<p>Lista definitiva: {{definitive_list_summary}}</p>
<p>Audiência prévia: {{hearing_summary}}</p>
<p>Reclamações: {{complaint_summary}}</p>
<p>Sorteios: {{lottery_summary}}</p>
<p>Desistências: {{withdrawals_summary}}</p>
<p>Enquadramento legal: {{legal_basis}}</p>
<p>Deliberação: {{deliberation_text}}</p>
<p>Observações: {{observations}}</p>
<p>Documento gerado em {{generated_at}}.</p>
HTML,
        ];
    }

    /**
     * @return list<string>
     */
    private function variables(string $content): array
    {
        preg_match_all('/{{\s*([A-Za-z0-9_\.]+)\s*}}/', $content, $matches);

        return array_values(array_unique($matches[1]));
    }
}
