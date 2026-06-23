<?php

namespace Database\Seeders;

use App\Models\Citizen;
use Illuminate\Database\Seeder;

class CitizenSeeder extends Seeder
{
    public function run(): void
    {
        $citizens = [
            [
                'name' => 'Ana Silva',
                'document_number' => 'CC12345678',
                'birth_date' => '1987-03-14',
                'phone' => '912345678',
                'email' => 'ana.silva@example.com',
                'address' => 'Rua Nova, 10',
                'notes' => 'Agregado monoparental.',
            ],
            [
                'name' => 'Joao Costa',
                'document_number' => 'CC23456789',
                'birth_date' => '1979-09-02',
                'phone' => '913456789',
                'email' => 'joao.costa@example.com',
                'address' => 'Rua do Mercado, 18',
                'notes' => 'Acompanha processo de candidatura.',
            ],
            [
                'name' => 'Maria Ferreira',
                'document_number' => 'CC34567890',
                'birth_date' => '1992-11-21',
                'phone' => '914567890',
                'email' => 'maria.ferreira@example.com',
                'address' => 'Praceta do Parque, 7',
                'notes' => null,
            ],
            [
                'name' => 'Carlos Santos',
                'document_number' => 'CC45678901',
                'birth_date' => '1983-06-08',
                'phone' => '915678901',
                'email' => 'carlos.santos@example.com',
                'address' => 'Largo da Fonte, 3',
                'notes' => 'Necessita de apoio documental.',
            ],
        ];

        foreach ($citizens as $citizen) {
            Citizen::updateOrCreate(
                ['document_number' => $citizen['document_number']],
                $citizen,
            );
        }
    }
}
