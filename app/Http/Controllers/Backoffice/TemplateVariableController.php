<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\TemplateVariableType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateVariableRequest;
use App\Models\TemplateVariable;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TemplateVariableController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', TemplateVariable::class);

        return view('backoffice.communications.variables.index', [
            'variables' => TemplateVariable::query()->orderBy('code')->paginate(30),
            'types' => TemplateVariableType::options(),
        ]);
    }

    public function store(StoreTemplateVariableRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', TemplateVariable::class);
        $variable = TemplateVariable::query()->create(
            $request->validated(),
        );
        $this->audit->record(
            AuditEvents::CREATE,
            $variable,
            'communications',
            'template_variable_created',
            'Variável de comunicação criada.',
        );

        return back()->with('success', 'Variável criada.');
    }

    public function update(StoreTemplateVariableRequest $request, TemplateVariable $templateVariable): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $templateVariable);
        $templateVariable->update($request->validated());
        $this->audit->record(
            AuditEvents::UPDATE,
            $templateVariable,
            'communications',
            'template_variable_updated',
            'Variável de comunicação atualizada.',
        );

        return back()->with('success', 'Variável atualizada.');
    }
}
