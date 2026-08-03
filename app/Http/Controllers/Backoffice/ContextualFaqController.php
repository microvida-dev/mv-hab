<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContextualFaqRequest;
use App\Http\Requests\UpdateContextualFaqRequest;
use App\Models\Contest;
use App\Models\ContextualFaq;
use App\Models\ContextualFaqCategory;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ContextualFaqController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAnyBackoffice', ContextualFaq::class);

        return view('backoffice.contextual-faqs.index', [
            'faqs' => $this->municipalScope
                ->contextualFaqs(
                    ContextualFaq::query(),
                    $this->currentUser(),
                )
                ->with(['category', 'contest'])
                ->orderBy('context_key')
                ->orderBy('sort_order')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('createBackoffice', ContextualFaq::class);

        return view('backoffice.contextual-faqs.create', $this->formData());
    }

    public function store(StoreContextualFaqRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', ContextualFaq::class);
        $this->authorizeContestContext($request->integer('contest_id'));
        $faq = new ContextualFaq($request->validated());
        $faq->forceFill([
            'created_by' => $this->authenticatedUser($request)->id,
            'updated_by' => $this->authenticatedUser($request)->id,
        ])->save();
        $this->audit->record(
            AuditEvents::CREATE,
            $faq,
            'communications',
            'contextual_faq_created',
            'FAQ contextual criada.',
        );

        return to_route('backoffice.contextual-faqs.index')->with('success', 'FAQ contextual criada.');
    }

    public function show(ContextualFaq $contextualFaq): View
    {
        Gate::authorize('viewBackoffice', $contextualFaq);

        return view('backoffice.contextual-faqs.edit', [
            'faq' => $contextualFaq,
            ...$this->formData(),
        ]);
    }

    public function edit(ContextualFaq $contextualFaq): View
    {
        Gate::authorize('updateBackoffice', $contextualFaq);

        return view('backoffice.contextual-faqs.edit', [
            'faq' => $contextualFaq,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateContextualFaqRequest $request, ContextualFaq $contextualFaq): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $contextualFaq);
        $this->authorizeContestContext($request->integer('contest_id'));
        $contextualFaq->fill($request->validated());
        $contextualFaq->forceFill(['updated_by' => $this->authenticatedUser($request)->id])->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $contextualFaq,
            'communications',
            'contextual_faq_updated',
            'FAQ contextual atualizada.',
        );

        return to_route('backoffice.contextual-faqs.index')->with('success', 'FAQ contextual atualizada.');
    }

    public function destroy(ContextualFaq $contextualFaq): RedirectResponse
    {
        Gate::authorize('deleteBackoffice', $contextualFaq);
        $contextualFaq->delete();
        $this->audit->record(
            AuditEvents::DELETE,
            $contextualFaq,
            'communications',
            'contextual_faq_deleted',
            'FAQ contextual removida.',
        );

        return to_route('backoffice.contextual-faqs.index')->with('success', 'FAQ contextual removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => ContextualFaqCategory::query()->active()->orderBy('sort_order')->get(),
            'contests' => $this->municipalScope
                ->contests(Contest::query(), $this->currentUser())
                ->orderBy('title')
                ->get(),
        ];
    }

    private function authorizeContestContext(int $contestId): void
    {
        if ($contestId === 0) {
            abort_unless(
                $this->platformScope->hasGlobalScope($this->currentUser()),
                403,
            );

            return;
        }

        abort_unless(
            $this->municipalScope
                ->contests(Contest::query(), $this->currentUser())
                ->whereKey($contestId)
                ->exists(),
            403,
        );
    }
}
