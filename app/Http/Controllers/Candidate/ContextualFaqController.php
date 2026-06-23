<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContextualFaq;
use App\Services\Support\ContextualFaqService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContextualFaqController extends Controller
{
    public function __construct(private readonly ContextualFaqService $faqs) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ContextualFaq::class);
        $contest = $request->integer('contest_id') > 0 ? Contest::query()->find($request->integer('contest_id')) : null;
        $contextKey = (string) $request->query('context', 'application_draft');
        $items = $this->faqs->resolve($contextKey, $contest, $request->query('q') ? (string) $request->query('q') : null);

        if ($request->integer('viewed') > 0) {
            $faq = $items->firstWhere('id', $request->integer('viewed'));
            if ($faq instanceof ContextualFaq) {
                $this->faqs->recordView($faq, $this->authenticatedUser($request));
            }
        }

        return view('candidate.contextual-faq.index', [
            'faqs' => $items,
            'contextKey' => $contextKey,
            'contest' => $contest,
        ]);
    }
}
