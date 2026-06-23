<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentRuleRequest;
use App\Http\Requests\UpdateRentRuleRequest;
use App\Models\RentRule;
use App\Models\RentRuleSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RentRuleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', RentRule::class);

        return view('backoffice.contracts.rent-rules.index', [
            'rules' => RentRule::query()->with('rentRuleSet')->orderBy('priority_order')->paginate(30),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', RentRule::class);

        return view('backoffice.contracts.rent-rules.create', ['ruleSets' => RentRuleSet::query()->orderBy('name')->get()]);
    }

    public function store(StoreRentRuleRequest $request): RedirectResponse
    {
        Gate::authorize('create', RentRule::class);
        $rule = RentRule::query()->create($this->normalized($request->validated()));

        return to_route('backoffice.contracts.rent-rule-sets.show', $rule->rent_rule_set_id)->with('success', 'Regra específica criada.');
    }

    public function edit(RentRule $rentRule): View
    {
        Gate::authorize('update', $rentRule);

        return view('backoffice.contracts.rent-rules.edit', ['rule' => $rentRule, 'ruleSets' => RentRuleSet::query()->orderBy('name')->get()]);
    }

    public function update(UpdateRentRuleRequest $request, RentRule $rentRule): RedirectResponse
    {
        Gate::authorize('update', $rentRule);
        $rentRule->update($this->normalized($request->validated()));

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
