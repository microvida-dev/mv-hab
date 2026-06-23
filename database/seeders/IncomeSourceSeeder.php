<?php

namespace Database\Seeders;

use App\Enums\IncomeSourceType;
use App\Models\IncomeSource;
use Illuminate\Database\Seeder;

class IncomeSourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (IncomeSourceType::cases() as $index => $type) {
            IncomeSource::query()->updateOrCreate(
                ['code' => $type->value],
                [
                    'name' => $type->label(),
                    'description' => 'Fonte de rendimento configurada pelo sistema.',
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ],
            );
        }
    }
}
