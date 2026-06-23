<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\TemplateVariableType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateVariableRequest;
use App\Models\TemplateVariable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TemplateVariableController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', TemplateVariable::class);

        return view('backoffice.communications.variables.index', [
            'variables' => TemplateVariable::query()->orderBy('code')->paginate(30),
            'types' => TemplateVariableType::options(),
        ]);
    }

    public function store(StoreTemplateVariableRequest $request): RedirectResponse
    {
        Gate::authorize('create', TemplateVariable::class);
        TemplateVariable::query()->create($request->validated());

        return back()->with('success', 'Variável criada.');
    }

    public function update(StoreTemplateVariableRequest $request, TemplateVariable $templateVariable): RedirectResponse
    {
        Gate::authorize('update', $templateVariable);
        $templateVariable->update($request->validated());

        return back()->with('success', 'Variável atualizada.');
    }
}
