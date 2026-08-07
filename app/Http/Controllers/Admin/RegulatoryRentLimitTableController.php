<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertRegulatoryRentLimitTableRequest;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentLimitTableManifest;
use App\Models\RentRuleSet;
use App\Services\Platform\PlatformMunicipalContextService;
use App\Services\Regulatory\RegulatoryRentLimitTableService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class RegulatoryRentLimitTableController extends Controller
{
    public function __construct(
        private readonly RegulatoryRentLimitTableService $tables,
        private readonly PlatformMunicipalContextService $municipalContext,
    ) {}

    public function edit(Request $request, AffordableRentRegulatoryProfile $regulatoryProfile): View
    {
        Gate::authorize('updateBackoffice', $regulatoryProfile);
        $actor = $this->authenticatedUser($request);
        $municipality = $this->municipalContext->requireMunicipality($actor);
        $this->assertProfileInContext($regulatoryProfile, $municipality->id);

        $ruleSets = RentRuleSet::query()
            ->where('regulatory_profile_id', $regulatoryProfile->id)
            ->whereHas('program', fn ($query) => $query->where('municipality_id', $municipality->id))
            ->with('program')
            ->orderBy('name')
            ->get();

        $selectedRuleSet = $request->integer('rent_rule_set_id') > 0
            ? $ruleSets->firstWhere('id', $request->integer('rent_rule_set_id'))
            : $ruleSets->first();
        $manifest = $selectedRuleSet instanceof RentRuleSet
            ? RentLimitTableManifest::query()
                ->with('rows')
                ->where('rent_rule_set_id', $selectedRuleSet->id)
                ->first()
            : null;

        return view('admin.regulatory-profiles.rent-limits', [
            'regulatoryProfile' => $regulatoryProfile,
            'municipality' => $municipality,
            'ruleSets' => $ruleSets,
            'selectedRuleSet' => $selectedRuleSet,
            'manifest' => $manifest,
        ]);
    }

    public function update(
        UpsertRegulatoryRentLimitTableRequest $request,
        AffordableRentRegulatoryProfile $regulatoryProfile,
    ): RedirectResponse {
        $manifest = $this->tables->upsert(
            $regulatoryProfile,
            $request->validated(),
            $this->authenticatedUser($request),
        );

        return to_route('admin.regulatory-profiles.rent-limits.edit', [
            'regulatoryProfile' => $regulatoryProfile,
            'rent_rule_set_id' => $manifest->rent_rule_set_id,
        ])->with('success', 'Tabela oficial de limites de renda validada e configurada.');
    }

    private function assertProfileInContext(AffordableRentRegulatoryProfile $profile, int $municipalityId): void
    {
        if ($profile->municipality_id !== null && (int) $profile->municipality_id !== $municipalityId) {
            abort(404);
        }
    }
}
