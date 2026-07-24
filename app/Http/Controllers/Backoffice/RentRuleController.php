<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentRuleRequest;
use App\Http\Requests\UpdateRentRuleRequest;
use App\Models\RentRule;
use App\Models\RentRuleSet;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RentRuleController extends Controller
{
    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', RentRule::class);

        return view('backoffice.contracts.rent-rules.index', [
            'rules' => $this->municipalScope
                ->rentRules(RentRule::query(), $this->authenticatedUser($request))
                ->with('rentRuleSet')
                ->orderBy('priority_order')
                ->paginate(30),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', RentRule::class);

        return view('backoffice.contracts.rent-rules.create', [
            'ruleSets' => $this->municipalScope
                ->rentRuleSets(RentRuleSet::query(), $this->authenticatedUser($request))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreRentRuleRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', RentRule::class);
        $data = $this->normalized($request->validated());
        $this->municipalScope
            ->rentRuleSets(RentRuleSet::query(), $this->authenticatedUser($request))
            ->findOrFail((int) $data['rent_rule_set_id']);
        $rule = RentRule::query()->create($data);

        return to_route('backoffice.contracts.rent-rule-sets.show', $rule->rent_rule_set_id)->with('success', 'Regra específica criada.');
    }

    public function edit(Request $request, RentRule $rentRule): View
    {
        Gate::authorize('updateBackoffice', $rentRule);

        return view('backoffice.contracts.rent-rules.edit', [
            'rule' => $rentRule,
            'ruleSets' => $this->municipalScope
                ->rentRuleSets(RentRuleSet::query(), $this->authenticatedUser($request))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateRentRuleRequest $request, RentRule $rentRule): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $rentRule);
        $data = $this->normalized($request->validated());
        $this->municipalScope
            ->rentRuleSets(RentRuleSet::query(), $this->authenticatedUser($request))
            ->findOrFail((int) $data['rent_rule_set_id']);
        $rentRule->update($data);

        return to_route('backoffice.contracts.rent-rule-sets.show', $rentRule->rent_rule_set_id)->with('success', 'Regra específica atualizada.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
