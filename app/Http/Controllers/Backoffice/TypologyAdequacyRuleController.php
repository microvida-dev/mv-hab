<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTypologyAdequacyRuleRequest;
use App\Http\Requests\UpdateTypologyAdequacyRuleRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Models\TypologyAdequacyRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TypologyAdequacyRuleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', TypologyAdequacyRule::class);

        return view('backoffice.allocation.typology-rules.index', [
            'rules' => TypologyAdequacyRule::query()->with(['program', 'contest'])->orderBy('priority_order')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', TypologyAdequacyRule::class);

        return view('backoffice.allocation.typology-rules.create', $this->formData());
    }

    public function store(StoreTypologyAdequacyRuleRequest $request): RedirectResponse
    {
        Gate::authorize('create', TypologyAdequacyRule::class);
        TypologyAdequacyRule::query()->create($request->validated());

        return to_route('backoffice.allocation.typology-rules.index')->with('success', 'Regra de tipologia criada.');
    }

    public function edit(TypologyAdequacyRule $typologyAdequacyRule): View
    {
        Gate::authorize('update', $typologyAdequacyRule);

        return view('backoffice.allocation.typology-rules.edit', $this->formData() + compact('typologyAdequacyRule'));
    }

    public function update(UpdateTypologyAdequacyRuleRequest $request, TypologyAdequacyRule $typologyAdequacyRule): RedirectResponse
    {
        Gate::authorize('update', $typologyAdequacyRule);
        $typologyAdequacyRule->update($request->validated());

        return to_route('backoffice.allocation.typology-rules.index')->with('success', 'Regra de tipologia atualizada.');
    }

    public function activate(Request $request, TypologyAdequacyRule $typologyAdequacyRule): RedirectResponse
    {
        Gate::authorize('update', $typologyAdequacyRule);
        $typologyAdequacyRule->update(['is_active' => true]);

        return back()->with('success', 'Regra ativada.');
    }

    public function deactivate(Request $request, TypologyAdequacyRule $typologyAdequacyRule): RedirectResponse
    {
        Gate::authorize('update', $typologyAdequacyRule);
        $typologyAdequacyRule->update(['is_active' => false]);

        return back()->with('success', 'Regra desativada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(),
            'contests' => Contest::query()->orderByDesc('created_at')->get(),
        ];
    }
}
