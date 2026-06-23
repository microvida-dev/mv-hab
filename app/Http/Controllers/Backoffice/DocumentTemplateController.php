<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentTemplateRequest;
use App\Http\Requests\UpdateDocumentTemplateRequest;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateVariable;
use App\Services\Documents\DocumentTemplateService;
use App\Services\Notifications\TemplateRenderingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentTemplateController extends Controller
{
    public function __construct(private readonly DocumentTemplateService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', DocumentTemplate::class);

        return view('backoffice.document-templates.index', ['templates' => DocumentTemplate::query()->with('activeVersion')->latest()->paginate(20)]);
    }

    public function create(): View
    {
        Gate::authorize('create', DocumentTemplate::class);

        return view('backoffice.document-templates.create');
    }

    public function store(StoreDocumentTemplateRequest $request): RedirectResponse
    {
        Gate::authorize('create', DocumentTemplate::class);
        $template = $this->service->store($request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.document-templates.show', $template);
    }

    public function show(DocumentTemplate $documentTemplate): View
    {
        Gate::authorize('view', $documentTemplate);
        $documentTemplate->load(['versions', 'activeVersion']);

        return view('backoffice.document-templates.show', compact('documentTemplate'));
    }

    public function edit(DocumentTemplate $documentTemplate): View
    {
        Gate::authorize('update', $documentTemplate);

        return view('backoffice.document-templates.edit', compact('documentTemplate'));
    }

    public function update(UpdateDocumentTemplateRequest $request, DocumentTemplate $documentTemplate): RedirectResponse
    {
        Gate::authorize('update', $documentTemplate);
        $this->service->update($documentTemplate, $request->validated(), $this->authenticatedUser($request));

        return to_route('backoffice.document-templates.show', $documentTemplate);
    }

    public function archive(DocumentTemplate $documentTemplate): RedirectResponse
    {
        Gate::authorize('update', $documentTemplate);
        $this->service->archive($documentTemplate, $this->currentUser());

        return back()->with('success', 'Modelo arquivado.');
    }

    public function preview(DocumentTemplate $documentTemplate, TemplateRenderingService $renderer): View
    {
        Gate::authorize('view', $documentTemplate);
        $version = $documentTemplate->activeVersion;

        if (! $version instanceof DocumentTemplateVersion) {
            $fallbackVersion = $documentTemplate->versions()->first();
            $version = $fallbackVersion instanceof DocumentTemplateVersion ? $fallbackVersion : null;
        }

        abort_unless($version instanceof DocumentTemplateVersion, 404);

        $variables = TemplateVariable::query()->where('is_active', true)->pluck('example_value', 'code')->all();
        $rendered = $renderer->render([
            'title' => $version->title ?? $documentTemplate->title,
            'body' => $version->body ?? $documentTemplate->body,
            'html_body' => $version->html_body ?? $documentTemplate->html_body,
            'header' => $version->header ?? $documentTemplate->header,
            'footer' => $version->footer ?? $documentTemplate->footer,
        ], $variables);

        return view('backoffice.document-templates.preview', compact('documentTemplate', 'rendered'));
    }
}
