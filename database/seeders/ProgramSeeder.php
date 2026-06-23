<?php

namespace Database\Seeders;

use App\Enums\ContestDeadlineType;
use App\Enums\ContestStatus;
use App\Enums\ProgramStatus;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $municipality = Municipality::query()->updateOrCreate(
            ['code' => 'MVHAB'],
            [
                'name' => 'Município MV HAB',
                'tax_number' => null,
                'contact_email' => 'habitacao@example.com',
                'settings' => ['public_portal' => true],
                'active' => true,
            ],
        );

        $administrator = User::query()->where('email', 'admin@example.com')->first();

        if ($administrator) {
            $administrator->update(['municipality_id' => $municipality->id]);
        }

        $program = Program::query()->updateOrCreate(
            ['slug' => 'arrendamento-acessivel-municipal'],
            [
                'municipality_id' => $municipality->id,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'name' => 'Programa Municipal de Arrendamento Acessível',
                'summary' => 'Programa de demonstração para divulgação de oportunidades municipais de arrendamento a custos acessíveis.',
                'description' => 'O programa organiza a informação pública sobre concursos municipais de Arrendamento Acessível. Os requisitos específicos são definidos em cada aviso de abertura.',
                'legal_basis' => 'Conteúdo institucional de demonstração. O enquadramento legal deve ser validado pelo município antes de produção.',
                'status' => ProgramStatus::Published->value,
                'starts_at' => today(),
                'ends_at' => today()->addYears(2),
                'published_at' => now()->subDay(),
            ],
        );

        $program->rules()->delete();
        $program->rules()->createMany([
            [
                'title' => 'Residência e ligação ao município',
                'description' => 'Os requisitos territoriais concretos são definidos no aviso de cada concurso.',
                'sort_order' => 0,
            ],
            [
                'title' => 'Rendimentos e composição do agregado',
                'description' => 'A candidatura futura exigirá informação do agregado e dos rendimentos, nos termos a publicar.',
                'sort_order' => 1,
            ],
        ]);

        $contest = Contest::query()->updateOrCreate(
            ['code' => 'CAA-DEMO-2026-01'],
            [
                'program_id' => $program->id,
                'created_by' => $administrator?->id,
                'updated_by' => $administrator?->id,
                'slug' => 'concurso-demonstracao-2026',
                'title' => 'Concurso de demonstração 2026',
                'summary' => 'Aviso público de demonstração para validar o portal e os prazos da plataforma MV HAB.',
                'description' => 'Este concurso contém apenas dados institucionais fictícios e serve para validação funcional do portal público.',
                'application_instructions' => 'Crie uma conta de candidato, finalize o Registo de Adesão e complete agregado, rendimentos, situação habitacional e documentos antes de submeter.',
                'status' => ContestStatus::Published->value,
                'opens_at' => now()->subDay(),
                'closes_at' => now()->addMonth(),
                'published_at' => now()->subDay(),
            ],
        );

        $contest->deadlines()->delete();
        $contest->deadlines()->create([
            'type' => ContestDeadlineType::Applications->value,
            'label' => 'Período de candidaturas',
            'starts_at' => $contest->opens_at,
            'ends_at' => $contest->closes_at,
            'description' => 'Prazo de demonstração para apresentação futura de candidaturas.',
            'sort_order' => 0,
        ]);
    }
}
