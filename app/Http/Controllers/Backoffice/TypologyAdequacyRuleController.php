<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTypologyAdequacyRuleRequest;
use App\Http\Requests\UpdateTypologyAdequacyRuleRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Regulatory\RegulatoryRuleSetLinkService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TypologyAdequacyRuleController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly RegulatoryRuleSetLinkService $regulatoryLink,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', TypologyAdequacyRule::class);

        return view('backoffice.allocation.typology-rules.index', [
            'rules' => $this->municipalScope
                ->typologyAdequacyRules(
                    TypologyAdequacyRule::query(),
                    $this->authenticatedUser($request),
                )
                ->with(['program', 'contest'])
                ->orderBy('priority_order')
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', TypologyAdequacyRule::class);

        return view(
            'backoffice.allocation.typology-rules.create',
            $this->formData($this->authenticatedUser($request)),
        );
    }

    public function store(StoreTypologyAdequacyRuleRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', TypologyAdequacyRule::class);
        TypologyAdequacyRule::query()->create($this->regulatoryLink->link(
            $request->validated(),
            $this->authenticatedUser($request),
        ));

        return to_route('backoffice.allocation.typology-rules.index')->with('success', 'Regra de tipologia criada.');
    }

    public function edit(
        Request $request,
        TypologyAdequacyRule $typologyAdequacyRule,
    ): View {
        Gate::authorize('updateBackoffice', $typologyAdequacyRule);

        return view(
            'backoffice.allocation.typology-rules.edit',
            $this->formData($this->authenticatedUser($request))
                + compact('typologyAdequacyRule'),
        );
    }

    public function update(UpdateTypologyAdequacyRuleRequest $request, TypologyAdequacyRule $typologyAdequacyRule): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $typologyAdequacyRule);
        $typologyAdequacyRule->update($this->regulatoryLink->link(
            $request->validated(),
            $this->authenticatedUser($request),
        ));

        return to_route('backoffice.allocation.typology-rules.index')->with('success', 'Regra de tipologia atualizada.');
    }

    public function activate(TypologyAdequacyRule $typologyAdequacyRule): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $typologyAdequacyRule);
        $typologyAdequacyRule->update(['is_active' => true]);

        return back()->with('success', 'Regra ativada.');
    }

    public function deactivate(TypologyAdequacyRule $typologyAdequacyRule): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $typologyAdequacyRule);
        $typologyAdequacyRule->update(['is_active' => false]);

        return back()->with('success', 'Regra desativada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(User $actor): array
    {
        return [
            'programs' => $this->municipalScope
                ->programs(Program::query(), $actor)
                ->orderBy('name')
                ->get(),
            'contests' => $this->municipalScope
                ->contests(Contest::query(), $actor)
                ->orderByDesc('created_at')
                ->get(),
        ];
    }
}
