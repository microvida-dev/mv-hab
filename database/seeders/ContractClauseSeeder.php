<?php

namespace Database\Seeders;

use App\Enums\ContractClauseStatus;
use App\Models\ContractClause;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ContractClauseSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::query()->first();

        if (! $program) {
            return;
        }

        ContractClause::query()->firstOrCreate(
            ['program_id' => $program->id, 'code' => 'DEMO-VALIDACAO-JURIDICA'],
            [
                'title' => 'Cláusula demo sujeita a validação jurídica',
                'body' => 'Esta cláusula é fictícia e deve ser substituída por texto validado juridicamente antes de produção.',
                'category' => 'general',
                'status' => ContractClauseStatus::Draft,
                'is_mandatory' => true,
                'sort_order' => 1,
            ],
        );
    }
}
