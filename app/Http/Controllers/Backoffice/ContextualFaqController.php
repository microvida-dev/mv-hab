<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContextualFaqRequest;
use App\Http\Requests\UpdateContextualFaqRequest;
use App\Models\Contest;
use App\Models\ContextualFaq;
use App\Models\ContextualFaqCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ContextualFaqController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ContextualFaq::class);

        return view('backoffice.contextual-faqs.index', [
            'faqs' => ContextualFaq::query()->with(['category', 'contest'])->orderBy('context_key')->orderBy('sort_order')->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ContextualFaq::class);

        return view('backoffice.contextual-faqs.create', $this->formData());
    }

    public function store(StoreContextualFaqRequest $request): RedirectResponse
    {
        $faq = new ContextualFaq($request->validated());
        $faq->forceFill([
            'created_by' => $this->authenticatedUser($request)->id,
            'updated_by' => $this->authenticatedUser($request)->id,
        ])->save();

        return to_route('backoffice.contextual-faqs.index')->with('success', 'FAQ contextual criada.');
    }

    public function show(ContextualFaq $contextualFaq): View
    {
        Gate::authorize('view', $contextualFaq);

        return view('backoffice.contextual-faqs.edit', [
            'faq' => $contextualFaq,
            ...$this->formData(),
        ]);
    }

    public function edit(ContextualFaq $contextualFaq): View
    {
        Gate::authorize('update', $contextualFaq);

        return view('backoffice.contextual-faqs.edit', [
            'faq' => $contextualFaq,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateContextualFaqRequest $request, ContextualFaq $contextualFaq): RedirectResponse
    {
        $contextualFaq->fill($request->validated());
        $contextualFaq->forceFill(['updated_by' => $this->authenticatedUser($request)->id])->save();

        return to_route('backoffice.contextual-faqs.index')->with('success', 'FAQ contextual atualizada.');
    }

    public function destroy(ContextualFaq $contextualFaq): RedirectResponse
    {
        Gate::authorize('delete', $contextualFaq);
        $contextualFaq->delete();

        return to_route('backoffice.contextual-faqs.index')->with('success', 'FAQ contextual removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => ContextualFaqCategory::query()->active()->orderBy('sort_order')->get(),
            'contests' => Contest::query()->orderBy('title')->get(),
        ];
    }
}
