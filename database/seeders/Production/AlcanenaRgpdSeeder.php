<?php

namespace Database\Seeders\Production;

use App\Enums\ConsentLegalBasis;
use App\Enums\MunicipalityOnboardingStatus;
use App\Enums\RetentionAction;
use App\Models\ConsentPurpose;
use App\Models\Municipality;
use App\Models\MunicipalityOnboardingRun;
use App\Models\RetentionPolicy;
use App\Models\User;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AlcanenaRgpdSeeder extends Seeder
{
    public const PLANNING_PURPOSE_CODE = 'alcanena_housing_policy_planning';

    public const RETENTION_POLICY_CODE = 'alcanena_personal_data_5y_post_contract_review';

    public function run(): void
    {
        DB::transaction(function (): void {
            $municipality = $this->municipality();
            $actor = $this->municipalAdministrator($municipality);

            $this->assertGlobalPurposes();
            $this->seedPlanningPurpose($municipality, $actor);
            $this->seedRetentionPolicy($municipality, $actor);
        }, 3);

        $this->command->info('Configuração RGPD municipal de Alcanena criada com retenção manual fail-closed.');
    }

    private function municipality(): Municipality
    {
        $municipality = Municipality::query()
            ->where('code', AlcanenaProductionSeeder::MUNICIPALITY_CODE)
            ->lockForUpdate()
            ->first();

        if (! $municipality instanceof Municipality) {
            throw new DomainException('O Município de Alcanena não existe.');
        }

        if (! $municipality->active) {
            throw new DomainException('O Município de Alcanena encontra-se inativo.');
        }

        return $municipality;
    }

    private function municipalAdministrator(Municipality $municipality): User
    {
        $run = MunicipalityOnboardingRun::query()
            ->where('municipality_code', AlcanenaProductionSeeder::MUNICIPALITY_CODE)
            ->where('municipality_id', $municipality->id)
            ->where('status', MunicipalityOnboardingStatus::Completed->value)
            ->whereNotNull('admin_user_id')
            ->latest('id')
            ->first();

        if (! $run instanceof MunicipalityOnboardingRun) {
            throw new DomainException('Não existe onboarding municipal concluído para o Município de Alcanena.');
        }

        $actor = User::query()->find($run->admin_user_id);

        if (! $actor instanceof User
            || (int) $actor->municipality_id !== (int) $municipality->id
            || $actor->status !== 'active') {
            throw new DomainException('O administrador municipal de Alcanena não é elegível.');
        }

        return $actor;
    }

    private function assertGlobalPurposes(): void
    {
        foreach ([
            'application_processing',
            'document_review',
            'municipal_communications',
            'optional_feedback',
        ] as $code) {
            $purpose = ConsentPurpose::withTrashed()
                ->where('code', $code)
                ->first();

            if (! $purpose instanceof ConsentPurpose) {
                throw new DomainException("A finalidade RGPD global {$code} não existe.");
            }

            if ($purpose->trashed()) {
                throw new DomainException("A finalidade RGPD global {$code} encontra-se eliminada.");
            }

            if ($purpose->municipality_id !== null) {
                throw new DomainException("A finalidade RGPD global {$code} está associada indevidamente a um Município.");
            }
        }
    }

    private function seedPlanningPurpose(Municipality $municipality, User $actor): void
    {
        $purpose = ConsentPurpose::withTrashed()
            ->where('code', self::PLANNING_PURPOSE_CODE)
            ->first();

        if ($purpose instanceof ConsentPurpose) {
            if ($purpose->trashed()) {
                throw new DomainException('A finalidade RGPD municipal de planeamento existe, mas encontra-se eliminada.');
            }

            if ((int) $purpose->municipality_id !== (int) $municipality->id) {
                throw new DomainException('A finalidade RGPD municipal de planeamento pertence a outro Município.');
            }

            return;
        }

        ConsentPurpose::query()->create([
            'municipality_id' => $municipality->id,
            'code' => self::PLANNING_PURPOSE_CODE,
            'name' => 'Planeamento municipal de habitação — Alcanena',
            'description' => 'Análise dos dados do programa para adequação da oferta à procura e planeamento das políticas de habitação do Município de Alcanena, nos limites definidos no artigo 34.º do Regulamento Municipal.',
            'legal_basis' => ConsentLegalBasis::PublicInterest->value,
            'is_required' => true,
            'is_active' => true,
            'requires_explicit_consent' => false,
            'retention_period_months' => null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function seedRetentionPolicy(Municipality $municipality, User $actor): void
    {
        $policy = RetentionPolicy::withTrashed()
            ->where('code', self::RETENTION_POLICY_CODE)
            ->first();

        if ($policy instanceof RetentionPolicy) {
            if ($policy->trashed()) {
                throw new DomainException('A política RGPD municipal de retenção existe, mas encontra-se eliminada.');
            }

            if ((int) $policy->municipality_id !== (int) $municipality->id) {
                throw new DomainException('A política RGPD municipal de retenção pertence a outro Município.');
            }

            return;
        }

        RetentionPolicy::query()->create([
            'municipality_id' => $municipality->id,
            'code' => self::RETENTION_POLICY_CODE,
            'name' => 'Dados pessoais do programa — revisão 5 anos após cessação contratual',
            'description' => 'Política de referência para o artigo 34.º, n.º 6. O prazo conta após a cessação da relação contratual ou segue outro prazo legal aplicável. A arquitetura atual não codifica esse evento de início, pelo que qualquer execução permanece sujeita a revisão manual.',
            'status' => 'draft',
            'entity_type' => 'App\\Models\\Contract',
            'document_type_id' => null,
            'retention_period_months' => 60,
            'retention_action' => RetentionAction::ReviewManually->value,
            'legal_basis' => 'Regulamento Municipal de Arrendamento Acessível de Alcanena — Edital n.º 1820/2024, artigo 34.º, n.º 6; Regulamento (UE) 2016/679.',
            'requires_manual_approval' => true,
            'created_by' => $actor->id,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }
}
