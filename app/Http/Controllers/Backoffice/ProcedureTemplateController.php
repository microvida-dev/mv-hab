<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublishProcedureTemplateRequest;
use App\Http\Requests\RenderProcedureTemplateRequest;
use App\Http\Requests\StoreProcedureTemplateRequest;
use App\Http\Requests\UpdateProcedureTemplateRequest;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ProcedureTemplate;
use App\Services\ProcedureTemplates\GeneratedProcedureDocumentService;
use App\Services\ProcedureTemplates\ProcedureTemplateService;
use App\Services\ProcedureTemplates\TemplateRenderingService;
use App\Services\ProcedureTemplates\TemplateVariableResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ProcedureTemplateController extends Controller
{
    public function __construct(
        private readonly ProcedureTemplateService $templates,
        private readonly TemplateVariableResolver $variables,
        private readonly TemplateRenderingService $renderer,
        private readonly GeneratedProcedureDocumentService $documents,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ProcedureTemplate::class);
        $templates = ProcedureTemplate::query()->latest()->paginate(20);

        return view('backoffice.procedure-templates.index', compact('templates'));
    }

    public function create(): View
    {
        Gate::authorize('create', ProcedureTemplate::class);

        return view('backoffice.procedure-templates.create');
    }

    public function store(StoreProcedureTemplateRequest $request): RedirectResponse
    {
        Gate::authorize('create', ProcedureTemplate::class);
        $template = $this->templates->store($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.procedure-templates.show', $template)->with('success', 'Minuta criada.');
    }

    public function show(ProcedureTemplate $procedureTemplate): View
    {
        Gate::authorize('view', $procedureTemplate);

        return view('backoffice.procedure-templates.show', compact('procedureTemplate'));
    }

    public function edit(ProcedureTemplate $procedureTemplate): View
    {
        Gate::authorize('update', $procedureTemplate);

        return view('backoffice.procedure-templates.edit', compact('procedureTemplate'));
    }

    public function update(UpdateProcedureTemplateRequest $request, ProcedureTemplate $procedureTemplate): RedirectResponse
    {
        Gate::authorize('update', $procedureTemplate);
        $template = $this->templates->update($procedureTemplate, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.procedure-templates.show', $template)->with('success', 'Minuta atualizada.');
    }

    public function publish(PublishProcedureTemplateRequest $request, ProcedureTemplate $procedureTemplate): RedirectResponse
    {
        Gate::authorize('publish', $procedureTemplate);
        $this->templates->publish($procedureTemplate, $this->authenticatedUser($request));

        return back()->with('success', 'Minuta publicada.');
    }

    public function render(RenderProcedureTemplateRequest $request, ProcedureTemplate $procedureTemplate): View
    {
        Gate::authorize('view', $procedureTemplate);
        $data = $request->validated();
        $application = isset($data['application_id']) ? Application::query()->whereKey((int) $data['application_id'])->first() : null;
        $contest = isset($data['contest_id']) ? Contest::query()->whereKey((int) $data['contest_id'])->first() : $application?->contest;
        $variables = $application ? $this->variables->forApplication($application, $this->authenticatedUser($request)) : ($contest ? $this->variables->forContest($contest) : []);
        $preview = $this->renderer->render($procedureTemplate, $variables);

        return view('backoffice.procedure-templates.preview', compact('procedureTemplate', 'preview', 'variables'));
    }

    public function generateDocument(RenderProcedureTemplateRequest $request, ProcedureTemplate $procedureTemplate): RedirectResponse
    {
        Gate::authorize('view', $procedureTemplate);
        $document = $this->documents->generate($procedureTemplate, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.generated-documents.show', $document)->with('success', 'Documento gerado.');
    }
}
