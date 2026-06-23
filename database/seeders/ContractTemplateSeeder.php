<?php

namespace Database\Seeders;

use App\Enums\ContractTemplateStatus;
use App\Models\ContractTemplate;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::query()->first();

        if (! $program) {
            return;
        }

        ContractTemplate::query()->firstOrCreate(
            ['program_id' => $program->id, 'name' => 'MINUTA DEMO - SUJEITA A VALIDACAO JURIDICA'],
            [
                'status' => ContractTemplateStatus::Draft,
                'version_number' => 1,
                'template_body' => 'MINUTA DEMO - SUJEITA A VALIDACAO JURIDICA. Contrato {{contract.number}} entre {{municipality.name}} e {{tenant.name}}, para a habitação {{housing.address}}, renda {{rent.amount}}, caução {{deposit.amount}}. {{clauses}}',
            ],
        );
    }
}
