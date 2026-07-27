<?php

namespace Database\Seeders\Demo;

use App\Enums\FeatureKey;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Entitlements\MunicipalityEntitlementService;
use App\Support\Demo\MunicipalApplicationDemoContext;
use Carbon\CarbonImmutable;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

final class MunicipalApplicationDemoAccessSeeder extends Seeder
{
    public const MUNICIPALITY_CODE = 'ALCANENA-DEMO';

    public const OPERATOR_ROLE_NAME =
        'demo_alcanena_operador_recolha';

    public const ANALYST_ROLE_NAME =
        'demo_alcanena_analista_candidaturas';

    public const VISIT_MANAGER_ROLE_NAME =
        'demo_alcanena_gestor_visitas';

    public const EXPORTER_ROLE_NAME =
        'demo_alcanena_exportador_candidaturas';

    public const OPERATOR_EMAIL =
        'operador.recolha.demo@mvhab.local';

    public const ANALYST_EMAIL =
        'analista.candidaturas.demo@mvhab.local';

    public const VISIT_MANAGER_EMAIL =
        'gestor.visitas.demo@mvhab.local';

    public const EXPORTER_EMAIL =
        'exportador.candidaturas.demo@mvhab.local';

    public const CANDIDATE_EMAIL =
        'joao.ferreira.demo@mvhab.local';

    private const ENTITLEMENT_JUSTIFICATION =
        'Ativação das funcionalidades candidaturais '
        .'para a demonstração municipal MV-HAB.';

    public function run(): void
    {
        $context = app(
            MunicipalApplicationDemoContext::class,
        );

        $context->assertSeederAllowed();

        /*
         * Garante previamente o catálogo central de roles
         * e permissions. O SystemAccessSeeder é idempotente.
         */
        $this->call(SystemAccessSeeder::class);

        DB::transaction(function () use ($context): void {
            $referenceDate = $context->referenceDate();
            $password = $context->userPassword();

            $municipality = $this->upsertMunicipality(
                $referenceDate,
            );

            $roles = $this->upsertMunicipalRoles(
                $municipality,
            );

            $users = $this->upsertUsers(
                $municipality,
                $roles,
                $password,
                $referenceDate,
            );

            $this->enableEntitlements(
                $municipality,
                $users['analyst'],
            );
        });
    }

    private function upsertMunicipality(
        CarbonImmutable $referenceDate,
    ): Municipality {
        $municipality = Municipality::query()
            ->firstOrNew([
                'code' => self::MUNICIPALITY_CODE,
            ]);

        $municipality->forceFill([
            'name' => 'Município de Alcanena — Demonstração MV-HAB',
            'tax_number' => null,
            'contact_email' => 'habitacao@demo.mvhab.test',
            'settings' => [
                'public_portal' => true,
                'demo_configuration' => true,
                'demo_seed_version' => '51B',
                'demo_reference_date' => $referenceDate->toDateString(),
                'demo_scope' => [
                    'candidate_applications',
                    'application_review',
                    'housing_visits',
                    'application_exports',
                ],
                'legal_validation_required_before_publication' => true,
            ],
            'active' => true,
        ])->save();

        return $municipality->refresh();
    }

    /**
     * @return array{
     *     operator: Role,
     *     analyst: Role,
     *     visit_manager: Role,
     *     exporter: Role
     * }
     */
    private function upsertMunicipalRoles(
        Municipality $municipality,
    ): array {
        return [
            'operator' => $this->upsertMunicipalRole(
                $municipality,
                self::OPERATOR_ROLE_NAME,
                'operador-recolha',
            ),
            'analyst' => $this->upsertMunicipalRole(
                $municipality,
                self::ANALYST_ROLE_NAME,
                'analista-candidaturas',
            ),
            'visit_manager' => $this->upsertMunicipalRole(
                $municipality,
                self::VISIT_MANAGER_ROLE_NAME,
                'gestor-visitas',
            ),
            'exporter' => $this->upsertMunicipalRole(
                $municipality,
                self::EXPORTER_ROLE_NAME,
                'exportador-candidaturas',
            ),
        ];
    }

    private function upsertMunicipalRole(
        Municipality $municipality,
        string $roleName,
        string $templateKey,
    ): Role {
        $template = app(
            MunicipalRoleTemplateRegistry::class,
        )->resolve($templateKey);

        $role = Role::query()->firstOrNew([
            'name' => $roleName,
        ]);

        if (
            $role->exists
            && (int) $role->municipality_id
                !== (int) $municipality->getKey()
        ) {
            throw new LogicException(
                "A role {$roleName} já existe fora "
                .'do Município de demonstração.',
            );
        }

        $role->forceFill([
            'municipality_id' => $municipality->getKey(),
            'label' => $template['label'],
            'description' => $template['description'],
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ])->save();

        $role->permissions()->sync(
            $template['permission_ids'],
        );

        return $role->refresh();
    }

    /**
     * @param array{
     *     operator: Role,
     *     analyst: Role,
     *     visit_manager: Role,
     *     exporter: Role
     * } $roles
     * @return array{
     *     operator: User,
     *     analyst: User,
     *     visit_manager: User,
     *     exporter: User,
     *     candidate: User
     * }
     */
    private function upsertUsers(
        Municipality $municipality,
        array $roles,
        string $password,
        CarbonImmutable $referenceDate,
    ): array {
        $candidateRole = Role::query()
            ->where('name', 'candidate')
            ->where('is_system', true)
            ->firstOrFail();

        /** @var array<string, array{
         *     name: string,
         *     email: string,
         *     role: Role,
         *     mfa_required: bool
         * }> $definitions
         */
        $definitions = [
            'operator' => [
                'name' => 'Operador de Recolha Demo',
                'email' => self::OPERATOR_EMAIL,
                'role' => $roles['operator'],
                'mfa_required' => true,
            ],
            'analyst' => [
                'name' => 'Analista de Candidaturas Demo',
                'email' => self::ANALYST_EMAIL,
                'role' => $roles['analyst'],
                'mfa_required' => true,
            ],
            'visit_manager' => [
                'name' => 'Gestor de Visitas Demo',
                'email' => self::VISIT_MANAGER_EMAIL,
                'role' => $roles['visit_manager'],
                'mfa_required' => true,
            ],
            'exporter' => [
                'name' => 'Exportador de Candidaturas Demo',
                'email' => self::EXPORTER_EMAIL,
                'role' => $roles['exporter'],
                'mfa_required' => true,
            ],
            'candidate' => [
                'name' => 'João Miguel Ferreira',
                'email' => self::CANDIDATE_EMAIL,
                'role' => $candidateRole,
                'mfa_required' => false,
            ],
        ];

        $users = [];

        foreach ($definitions as $key => $definition) {
            $user = User::query()->firstOrNew([
                'email' => $definition['email'],
            ]);

            if (
                $user->exists
                && (int) $user->municipality_id
                    !== (int) $municipality->getKey()
            ) {
                throw new LogicException(
                    "O utilizador {$definition['email']} "
                    .'já pertence a outro Município.',
                );
            }

            $attributes = [
                'municipality_id' => $municipality->getKey(),
                'name' => $definition['name'],
                'status' => 'active',
                'email_verified_at' => $referenceDate,
                'mfa_required' => $definition['mfa_required'],
                'internal_notes' => 'Conta fictícia criada exclusivamente '
                    .'para a demonstração municipal MV-HAB.',
                'deactivated_at' => null,
                'deactivated_by' => null,
                'reactivated_at' => null,
                'reactivated_by' => null,
            ];

            if (
                ! $user->exists
                || ! Hash::check(
                    $password,
                    (string) $user->getAuthPassword(),
                )
            ) {
                $attributes['password'] = $password;
            }

            $user->forceFill($attributes)->save();

            /*
             * Cada utilizador demo recebe exatamente uma role.
             * Não são preservadas roles estranhas ou antigas.
             */
            $user->roles()->sync([
                (int) $definition['role']->getKey(),
            ]);

            $users[$key] = $user->refresh();
        }

        /** @var array{
         *     operator: User,
         *     analyst: User,
         *     visit_manager: User,
         *     exporter: User,
         *     candidate: User
         * } $users
         */
        return $users;
    }

    private function enableEntitlements(
        Municipality $municipality,
        User $actor,
    ): void {
        $entitlements = app(
            MunicipalityEntitlementService::class,
        );

        /*
         * A ordem é obrigatória devido às dependências
         * declaradas no enum FeatureKey.
         */
        foreach ([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
            FeatureKey::ApplicationExport,
        ] as $feature) {
            $entitlements->enableFor(
                municipality: $municipality,
                feature: $feature,
                actor: $actor,
                justification: self::ENTITLEMENT_JUSTIFICATION,
            );
        }
    }
}
