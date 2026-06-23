<?php

namespace Database\Seeders;

use App\Enums\ConsentLegalBasis;
use App\Models\ConsentPurpose;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConsentPurposeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();

        foreach ($this->purposes() as $purpose) {
            ConsentPurpose::query()->updateOrCreate(
                ['code' => $purpose['code']],
                [
                    ...$purpose,
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
     *     legal_basis: string,
     *     is_required: bool,
     *     is_active: bool,
     *     requires_explicit_consent: bool,
     *     retention_period_months: int
     * }>
     */
    private function purposes(): array
    {
        return [
            [
                'code' => 'application_processing',
                'name' => 'Tratamento de candidatura',
                'description' => 'Tratamento necessário para receber, instruir e decidir candidaturas municipais de arrendamento acessível.',
                'legal_basis' => ConsentLegalBasis::PublicInterest->value,
                'is_required' => true,
                'is_active' => true,
                'requires_explicit_consent' => false,
                'retention_period_months' => 120,
            ],
            [
                'code' => 'document_review',
                'name' => 'Validação documental',
                'description' => 'Tratamento de documentos declarativos e comprovativos submetidos pelo candidato.',
                'legal_basis' => ConsentLegalBasis::LegalObligation->value,
                'is_required' => true,
                'is_active' => true,
                'requires_explicit_consent' => false,
                'retention_period_months' => 120,
            ],
            [
                'code' => 'municipal_communications',
                'name' => 'Comunicações processuais',
                'description' => 'Comunicações eletrónicas e administrativas relacionadas com o processo municipal.',
                'legal_basis' => ConsentLegalBasis::PublicInterest->value,
                'is_required' => true,
                'is_active' => true,
                'requires_explicit_consent' => false,
                'retention_period_months' => 60,
            ],
            [
                'code' => 'optional_feedback',
                'name' => 'Contacto para melhoria do serviço',
                'description' => 'Contacto opcional para recolha de opinião sobre a experiência de utilização da plataforma.',
                'legal_basis' => ConsentLegalBasis::Consent->value,
                'is_required' => false,
                'is_active' => true,
                'requires_explicit_consent' => true,
                'retention_period_months' => 24,
            ],
        ];
    }
}
