<?php

namespace Tests\Feature\Seeders;

use App\Enums\FeatureKey;
use App\Models\AuditEvent;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Entitlements\MunicipalityEntitlementService;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunicipalApplicationDemoAccessSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MVHAB-Demo-2026!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';

        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );
        config()->set(
            'mvhab.municipal_application_demo.reference_date',
            '2026-07-27',
        );
        config()->set(
            'mvhab.municipal_application_demo.user_password',
            self::PASSWORD,
        );

        CarbonImmutable::setTestNow(
            CarbonImmutable::create(
                2026,
                7,
                27,
                12,
                0,
                timezone: 'Europe/Lisbon',
            ),
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_seeder_creates_an_isolated_demo_municipality(): void
    {
        $this->seedDemo();

        $municipality = Municipality::query()
            ->where(
                'code',
                MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
            )
            ->sole();

        $this->assertSame(
            'Município de Alcanena — Demonstração MV-HAB',
            $municipality->name,
        );
        $this->assertSame(
            'habitacao@demo.mvhab.test',
            $municipality->contact_email,
        );
        $this->assertNull($municipality->tax_number);
        $this->assertTrue($municipality->active);

        $this->assertTrue(
            (bool) data_get(
                $municipality->settings,
                'demo_configuration',
            ),
        );
        $this->assertTrue(
            (bool) data_get(
                $municipality->settings,
                'legal_validation_required_before_publication',
            ),
        );
        $this->assertSame(
            '51B',
            data_get(
                $municipality->settings,
                'demo_seed_version',
            ),
        );
        $this->assertSame(
            '2026-07-27',
            data_get(
                $municipality->settings,
                'demo_reference_date',
            ),
        );
        $this->assertSame([
            'candidate_applications',
            'application_review',
            'housing_visits',
            'application_exports',
        ], data_get(
            $municipality->settings,
            'demo_scope',
        ));

        $this->assertDatabaseMissing('municipalities', [
            'code' => 'ALCANENA',
            'name' => 'Município de Alcanena — Demonstração MV-HAB',
        ]);
    }

    public function test_seeder_creates_exact_least_privilege_municipal_roles(): void
    {
        $this->seedDemo();

        $municipality = $this->demoMunicipality();

        $this->assertRoleMatchesTemplate(
            $municipality,
            MunicipalApplicationDemoAccessSeeder::OPERATOR_ROLE_NAME,
            'operador-recolha',
        );
        $this->assertRoleMatchesTemplate(
            $municipality,
            MunicipalApplicationDemoAccessSeeder::ANALYST_ROLE_NAME,
            'analista-candidaturas',
        );
        $this->assertRoleMatchesTemplate(
            $municipality,
            MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_ROLE_NAME,
            'gestor-visitas',
        );
        $this->assertRoleMatchesTemplate(
            $municipality,
            MunicipalApplicationDemoAccessSeeder::EXPORTER_ROLE_NAME,
            'exportador-candidaturas',
        );
        $this->assertRoleMatchesTemplate(
            $municipality,
            MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_ROLE_NAME,
            'analista-candidaturas-exportacao',
        );

        $this->assertSame(
            5,
            Role::query()
                ->where('municipality_id', $municipality->id)
                ->where('scope', 'municipal')
                ->where('is_system', false)
                ->count(),
        );
    }

    public function test_seeder_creates_exact_demo_users_and_role_assignments(): void
    {
        $this->seedDemo();

        $municipality = $this->demoMunicipality();

        foreach ($this->expectedUsers() as $email => $expected) {
            $user = User::query()
                ->where('email', $email)
                ->sole();

            $this->assertSame(
                $municipality->id,
                $user->municipality_id,
                $email,
            );
            $this->assertSame('active', $user->status, $email);
            $this->assertSame(
                '2026-07-27',
                $user->email_verified_at?->toDateString(),
                $email,
            );
            $this->assertSame(
                $expected['mfa_required'],
                $user->mfa_required,
                $email,
            );
            $this->assertTrue(
                Hash::check(
                    self::PASSWORD,
                    (string) $user->getAuthPassword(),
                ),
                $email,
            );

            $this->assertSame(
                [$expected['role']],
                $user->roles()
                    ->orderBy('roles.name')
                    ->pluck('roles.name')
                    ->map(
                        fn (mixed $role): string => (string) $role,
                    )
                    ->all(),
                $email,
            );

            $this->assertFalse(
                $user->hasRole('administrator'),
                $email,
            );
            $this->assertFalse(
                $user->hasPermission(
                    $this->globalWildcardPermission(),
                ),
                $email,
            );
        }

        $operator = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::OPERATOR_EMAIL,
            )
            ->sole();
        $analyst = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EMAIL,
            )
            ->sole();
        $visitManager = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_EMAIL,
            )
            ->sole();
        $exporter = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::EXPORTER_EMAIL,
            )
            ->sole();
        $analystExport = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_EMAIL,
            )
            ->sole();

        $this->assertFalse(
            $operator->hasPermission('documents.approve'),
        );
        $this->assertFalse(
            $operator->hasPermission('applications.export'),
        );
        $this->assertFalse(
            $operator->hasPermission('visits.view'),
        );

        $this->assertTrue(
            $analyst->hasPermission('documents.approve'),
        );
        $this->assertFalse(
            $analyst->hasPermission('applications.export'),
        );
        $this->assertFalse(
            $analyst->hasPermission('visits.view'),
        );

        $this->assertTrue(
            $visitManager->hasPermission('visits.confirm'),
        );
        $this->assertTrue(
            $visitManager->hasPermission(
                'visits.availabilities.generate_slots',
            ),
        );
        $this->assertFalse(
            $visitManager->hasPermission('applications.view'),
        );
        $this->assertFalse(
            $visitManager->hasPermission('documents.view'),
        );

        $this->assertTrue(
            $exporter->hasPermission('applications.export'),
        );
        $this->assertTrue(
            $exporter->hasPermission('reports.export'),
        );
        $this->assertFalse(
            $exporter->hasPermission('documents.view'),
        );
        $this->assertFalse(
            $exporter->hasPermission('visits.view'),
        );

        $this->assertTrue(
            $analystExport->hasPermission('documents.approve'),
        );
        $this->assertTrue(
            $analystExport->hasPermission('applications.export'),
        );
        $this->assertTrue(
            $analystExport->hasPermission('reports.export'),
        );
        $this->assertFalse(
            $analystExport->hasPermission('reports.export_sensitive'),
        );
        $this->assertFalse(
            $analystExport->hasPermission('roles.view'),
        );
    }

    public function test_seeder_enables_only_the_required_application_features(): void
    {
        $this->seedDemo();

        $municipality = $this->demoMunicipality();

        $features = app(
            MunicipalityEntitlementService::class,
        )->activeFor($municipality)
            ->map(
                fn (FeatureKey $feature): string => $feature->value,
            )
            ->sort()
            ->values()
            ->all();

        $expected = [
            FeatureKey::ApplicationExport->value,
            FeatureKey::ApplicationIntake->value,
            FeatureKey::ApplicationReview->value,
        ];
        sort($expected);

        $this->assertSame($expected, $features);

        $this->assertSame(
            3,
            MunicipalityFeatureEntitlement::query()
                ->where('municipality_id', $municipality->id)
                ->where('enabled', true)
                ->count(),
        );

        $analystExport = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_EMAIL,
            )
            ->sole();

        $events = AuditEvent::query()
            ->where('municipality_id', $municipality->id)
            ->where(
                'event_code',
                'municipality_feature_enabled',
            )
            ->get();

        $this->assertCount(3, $events);
        $this->assertTrue(
            $events->every(
                fn (AuditEvent $event): bool => $event->user_id === $analystExport->id,
            ),
        );
    }

    public function test_seeder_is_idempotent_and_preserves_stable_identifiers(): void
    {
        $this->seedDemo();

        $municipality = $this->demoMunicipality();

        $firstUserIds = User::query()
            ->whereIn(
                'email',
                array_keys($this->expectedUsers()),
            )
            ->orderBy('email')
            ->pluck('id', 'email')
            ->all();

        $firstPasswordHashes = User::query()
            ->whereIn(
                'email',
                array_keys($this->expectedUsers()),
            )
            ->orderBy('email')
            ->pluck('password', 'email')
            ->all();

        $firstRoleIds = Role::query()
            ->whereIn(
                'name',
                $this->expectedRoleNames(),
            )
            ->orderBy('name')
            ->pluck('id', 'name')
            ->all();

        $firstEntitlementIds =
            MunicipalityFeatureEntitlement::query()
                ->where('municipality_id', $municipality->id)
                ->orderBy('feature_key')
                ->pluck('id', 'feature_key')
                ->all();

        $this->seedDemo();

        $this->assertSame(
            $municipality->id,
            $this->demoMunicipality()->id,
        );
        $this->assertSame(
            $firstUserIds,
            User::query()
                ->whereIn(
                    'email',
                    array_keys($this->expectedUsers()),
                )
                ->orderBy('email')
                ->pluck('id', 'email')
                ->all(),
        );
        $this->assertSame(
            $firstPasswordHashes,
            User::query()
                ->whereIn(
                    'email',
                    array_keys($this->expectedUsers()),
                )
                ->orderBy('email')
                ->pluck('password', 'email')
                ->all(),
            'A reexecução do seeder não deve voltar a gerar '
            .'os hashes das passwords demo.',
        );
        $this->assertSame(
            $firstRoleIds,
            Role::query()
                ->whereIn(
                    'name',
                    $this->expectedRoleNames(),
                )
                ->orderBy('name')
                ->pluck('id', 'name')
                ->all(),
        );
        $this->assertSame(
            $firstEntitlementIds,
            MunicipalityFeatureEntitlement::query()
                ->where(
                    'municipality_id',
                    $municipality->id,
                )
                ->orderBy('feature_key')
                ->pluck('id', 'feature_key')
                ->all(),
        );

        $userIds = array_values($firstUserIds);
        $roleIds = array_values($firstRoleIds);

        $this->assertSame(
            6,
            DB::table('role_user')
                ->whereIn('user_id', $userIds)
                ->count(),
        );
        $this->assertSame(
            3,
            AuditEvent::query()
                ->where(
                    'municipality_id',
                    $municipality->id,
                )
                ->where(
                    'event_code',
                    'municipality_feature_enabled',
                )
                ->count(),
        );
        $this->assertSame(
            count(array_unique(
                DB::table('permission_role')
                    ->whereIn('role_id', $roleIds)
                    ->pluck('permission_id')
                    ->map(
                        fn (mixed $id): int => (int) $id,
                    )
                    ->all(),
            )),
            DB::table('permission_role')
                ->whereIn('role_id', $roleIds)
                ->distinct()
                ->count('permission_id'),
        );
    }

    private function seedDemo(): void
    {
        $this->seed(MunicipalApplicationDemoSeeder::class);
    }

    private function demoMunicipality(): Municipality
    {
        return Municipality::query()
            ->where(
                'code',
                MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
            )
            ->sole();
    }

    private function assertRoleMatchesTemplate(
        Municipality $municipality,
        string $roleName,
        string $templateKey,
    ): void {
        $template = app(
            MunicipalRoleTemplateRegistry::class,
        )->resolve($templateKey);

        $role = Role::query()
            ->where('name', $roleName)
            ->sole();

        $this->assertSame(
            $municipality->id,
            $role->municipality_id,
        );
        $this->assertSame(
            $template['label'],
            $role->label,
        );
        $this->assertSame(
            $template['description'],
            $role->description,
        );
        $this->assertSame('municipal', $role->scope);
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->is_active);
        $this->assertTrue($role->isMunicipalCustom());
        $this->assertSame($template['key'], $role->template_key);
        $this->assertSame($template['version'], $role->template_version);
        $this->assertSame(
            $template['fingerprint'],
            $role->template_fingerprint,
        );

        $this->assertEqualsCanonicalizing(
            $template['permissions'],
            $role->permissions()
                ->pluck('name')
                ->map(
                    fn (mixed $permission): string => (string) $permission,
                )
                ->all(),
        );
        $this->assertFalse(
            $role->hasPermission(
                $this->globalWildcardPermission(),
            ),
        );
    }

    private function globalWildcardPermission(): string
    {
        return chr(42);
    }

    /**
     * @return array<string, array{role: string, mfa_required: bool}>
     */
    private function expectedUsers(): array
    {
        return [
            MunicipalApplicationDemoAccessSeeder::OPERATOR_EMAIL => [
                'role' => MunicipalApplicationDemoAccessSeeder::OPERATOR_ROLE_NAME,
                'mfa_required' => true,
            ],
            MunicipalApplicationDemoAccessSeeder::ANALYST_EMAIL => [
                'role' => MunicipalApplicationDemoAccessSeeder::ANALYST_ROLE_NAME,
                'mfa_required' => true,
            ],
            MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_EMAIL => [
                'role' => MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_ROLE_NAME,
                'mfa_required' => true,
            ],
            MunicipalApplicationDemoAccessSeeder::EXPORTER_EMAIL => [
                'role' => MunicipalApplicationDemoAccessSeeder::EXPORTER_ROLE_NAME,
                'mfa_required' => true,
            ],
            MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_EMAIL => [
                'role' => MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_ROLE_NAME,
                'mfa_required' => true,
            ],
            MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL => [
                'role' => 'candidate',
                'mfa_required' => false,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function expectedRoleNames(): array
    {
        return [
            MunicipalApplicationDemoAccessSeeder::OPERATOR_ROLE_NAME,
            MunicipalApplicationDemoAccessSeeder::ANALYST_ROLE_NAME,
            MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_ROLE_NAME,
            MunicipalApplicationDemoAccessSeeder::EXPORTER_ROLE_NAME,
            MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_ROLE_NAME,
        ];
    }
}
