<?php

namespace App\Http\Controllers\Backoffice\Platform;

use App\Enums\FeatureKey;
use App\Exceptions\FeatureDependencyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DisableMunicipalityFeatureRequest;
use App\Http\Requests\EnableMunicipalityFeatureRequest;
use App\Models\AuditEvent;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Services\Entitlements\MunicipalityEntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MunicipalityFeatureController extends Controller
{
    private const AUDIT_EVENTS = [
        'municipality_feature_enabled',
        'municipality_feature_disabled',
    ];

    public function __construct(private readonly MunicipalityEntitlementService $entitlements) {}

    public function index(): View
    {
        Gate::authorize('viewAny', MunicipalityFeatureEntitlement::class);

        return view('backoffice.platform.municipality-features.index', [
            'municipalities' => Municipality::query()
                ->with('featureEntitlements')
                ->orderBy('name')
                ->paginate(25),
            'features' => FeatureKey::cases(),
        ]);
    }

    public function show(Municipality $municipality): View
    {
        Gate::authorize('view', [MunicipalityFeatureEntitlement::class, $municipality]);

        return view('backoffice.platform.municipality-features.show', [
            'municipality' => $municipality,
            'featureStates' => $this->featureStates($municipality),
        ]);
    }

    public function enable(
        EnableMunicipalityFeatureRequest $request,
        Municipality $municipality,
        FeatureKey $feature,
    ): RedirectResponse {
        try {
            $this->entitlements->enableFor(
                $municipality,
                $feature,
                $this->authenticatedUser($request),
                (string) $request->validated('justification'),
            );
        } catch (FeatureDependencyException $exception) {
            return back()->withErrors(['feature' => $exception->getMessage()]);
        }

        return back()->with('success', $feature->label().' foi ativada.');
    }

    public function disable(
        DisableMunicipalityFeatureRequest $request,
        Municipality $municipality,
        FeatureKey $feature,
    ): RedirectResponse {
        try {
            $this->entitlements->disableFor(
                $municipality,
                $feature,
                $this->authenticatedUser($request),
                (string) $request->validated('justification'),
            );
        } catch (FeatureDependencyException $exception) {
            return back()->withErrors(['feature' => $exception->getMessage()]);
        }

        return back()->with('success', $feature->label().' foi desativada.');
    }

    public function audit(Municipality $municipality): View
    {
        Gate::authorize('audit', [MunicipalityFeatureEntitlement::class, $municipality]);

        return view('backoffice.platform.municipality-features.audit', [
            'municipality' => $municipality,
            'events' => AuditEvent::query()
                ->with('user:id,name')
                ->where('auditable_type', $municipality->getMorphClass())
                ->where('auditable_id', $municipality->getKey())
                ->whereIn('event_code', self::AUDIT_EVENTS)
                ->latest('occurred_at')
                ->paginate(25),
        ]);
    }

    /**
     * @return list<array{
     *     feature: FeatureKey,
     *     enabled: bool,
     *     dependencies: list<FeatureKey>,
     *     can_enable: bool,
     *     can_disable: bool,
     *     blocked_reason: string|null
     * }>
     */
    private function featureStates(Municipality $municipality): array
    {
        $active = $this->entitlements->activeFor($municipality);

        $states = collect(FeatureKey::cases())
            ->map(function (FeatureKey $feature) use ($active): array {
                $enabled = $active->contains($feature);
                $missingDependencies = collect($feature->dependencies())
                    ->reject(fn (FeatureKey $dependency): bool => $active->contains($dependency));
                $activeDependants = collect(FeatureKey::cases())
                    ->filter(fn (FeatureKey $candidate): bool => in_array($feature, $candidate->dependencies(), true))
                    ->filter(fn (FeatureKey $candidate): bool => $active->contains($candidate));

                return [
                    'feature' => $feature,
                    'enabled' => $enabled,
                    'dependencies' => $feature->dependencies(),
                    'can_enable' => ! $enabled && $missingDependencies->isEmpty(),
                    'can_disable' => $enabled && $activeDependants->isEmpty(),
                    'blocked_reason' => $missingDependencies->isNotEmpty()
                        ? 'Requer '.$missingDependencies->map(fn (FeatureKey $dependency): string => $dependency->label())->join(' e ').'.'
                        : ($activeDependants->isNotEmpty()
                            ? 'Não pode desativar enquanto '.$activeDependants->map(fn (FeatureKey $dependant): string => $dependant->label())->join(' ou ').' estiver ativa.'
                            : null),
                ];
            })
            ->values()
            ->all();

        return array_values($states);
    }
}
