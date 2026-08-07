<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryConfigurationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegulatoryProfileRequest;
use App\Http\Requests\UpdateRegulatoryProfileRequest;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\User;
use App\Services\Platform\PlatformMunicipalContextService;
use App\Services\Regulatory\RegulatoryProfileManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class RegulatoryProfileController extends Controller
{
    public function __construct(
        private readonly RegulatoryProfileManagementService $profiles,
        private readonly PlatformMunicipalContextService $municipalContext,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', AffordableRentRegulatoryProfile::class);
        $actor = $this->authenticatedUser($request);
        $municipality = $this->municipalContext->requireMunicipality($actor);

        $profiles = AffordableRentRegulatoryProfile::query()
            ->with(['municipality', 'parentProfile'])
            ->where(fn ($query) => $query
                ->whereNull('municipality_id')
                ->orWhere('municipality_id', $municipality->id))
            ->orderBy('legal_regime')
            ->orderBy('municipality_id')
            ->orderByDesc('effective_from')
            ->paginate(20);

        return view('admin.regulatory-profiles.index', compact('profiles', 'municipality'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', AffordableRentRegulatoryProfile::class);
        $actor = $this->authenticatedUser($request);

        return view('admin.regulatory-profiles.create', $this->formData($actor));
    }

    public function store(StoreRegulatoryProfileRequest $request): RedirectResponse
    {
        $profile = $this->profiles->create(
            $request->validated(),
            $this->authenticatedUser($request),
        );

        return to_route('admin.regulatory-profiles.show', $profile)
            ->with('success', 'Perfil regulamentar criado em rascunho.');
    }

    public function show(Request $request, AffordableRentRegulatoryProfile $regulatoryProfile): View
    {
        Gate::authorize('viewBackoffice', $regulatoryProfile);
        $actor = $this->authenticatedUser($request);
        $this->assertProfileInContext($regulatoryProfile, $actor);

        $regulatoryProfile->load([
            'municipality',
            'parentProfile',
            'municipalOverlays.municipality',
            'rentLimitTableManifests.rows',
            'rentLimitTableManifests.rentRuleSet.program',
        ])->loadCount('snapshots');

        $ruleSetCounts = [
            'eligibility' => $regulatoryProfile->getConnection()->table('eligibility_rule_sets')->where('regulatory_profile_id', $regulatoryProfile->id)->count(),
            'typology' => $regulatoryProfile->getConnection()->table('typology_adequacy_rules')->where('regulatory_profile_id', $regulatoryProfile->id)->count(),
            'allocation' => $regulatoryProfile->getConnection()->table('allocation_rule_sets')->where('regulatory_profile_id', $regulatoryProfile->id)->count(),
            'rent' => $regulatoryProfile->getConnection()->table('rent_rule_sets')->where('regulatory_profile_id', $regulatoryProfile->id)->count(),
        ];

        return view('admin.regulatory-profiles.show', compact('regulatoryProfile', 'ruleSetCounts'));
    }

    public function edit(Request $request, AffordableRentRegulatoryProfile $regulatoryProfile): View
    {
        Gate::authorize('updateBackoffice', $regulatoryProfile);
        $actor = $this->authenticatedUser($request);
        $this->assertProfileInContext($regulatoryProfile, $actor);

        return view('admin.regulatory-profiles.edit', [
            'regulatoryProfile' => $regulatoryProfile->load('parentProfile'),
            ...$this->formData($actor),
        ]);
    }

    public function update(
        UpdateRegulatoryProfileRequest $request,
        AffordableRentRegulatoryProfile $regulatoryProfile,
    ): RedirectResponse {
        $actor = $this->authenticatedUser($request);
        $this->assertProfileInContext($regulatoryProfile, $actor);
        $this->profiles->update($regulatoryProfile, $request->validated(), $actor);

        return to_route('admin.regulatory-profiles.show', $regulatoryProfile)
            ->with('success', 'Perfil regulamentar atualizado.');
    }

    public function activate(Request $request, AffordableRentRegulatoryProfile $regulatoryProfile): RedirectResponse
    {
        Gate::authorize('activateBackoffice', $regulatoryProfile);
        $actor = $this->authenticatedUser($request);
        $this->assertProfileInContext($regulatoryProfile, $actor);
        $this->profiles->activate($regulatoryProfile, $actor);

        return back()->with('success', 'Perfil regulamentar ativado.');
    }

    public function archive(Request $request, AffordableRentRegulatoryProfile $regulatoryProfile): RedirectResponse
    {
        Gate::authorize('archiveBackoffice', $regulatoryProfile);
        $actor = $this->authenticatedUser($request);
        $this->assertProfileInContext($regulatoryProfile, $actor);
        $this->profiles->archive($regulatoryProfile, $actor);

        return back()->with('success', 'Perfil regulamentar arquivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(User $actor): array
    {
        $municipality = $this->municipalContext->requireMunicipality($actor);

        return [
            'municipality' => $municipality,
            'parentProfiles' => AffordableRentRegulatoryProfile::query()
                ->whereNull('municipality_id')
                ->where('status', '!=', 'archived')
                ->orderBy('legal_regime')
                ->orderByDesc('effective_from')
                ->get(),
            'legalRegimes' => AffordableRentLegalRegime::options(),
            'configurationStatuses' => RegulatoryConfigurationStatus::options(),
        ];
    }

    private function assertProfileInContext(AffordableRentRegulatoryProfile $profile, User $actor): void
    {
        $municipality = $this->municipalContext->requireMunicipality($actor);

        if ($profile->municipality_id !== null && (int) $profile->municipality_id !== (int) $municipality->id) {
            abort(404);
        }
    }
}
