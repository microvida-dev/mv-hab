<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\ApplicationReviewPublicationResult;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApplicationReviewResultController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'viewAny',
            ApplicationReviewPublicationResult::class,
        );
        $results = ApplicationReviewPublicationResult::query()
            ->with(['publication.contest.program'])
            ->where('user_id', $user->id)
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(20);

        return view('candidate.application-review-results.index', [
            'results' => $results,
        ]);
    }

    public function show(
        Request $request,
        ApplicationReviewPublicationResult $reviewResult,
    ): View {
        $user = $this->authenticatedUser($request);
        Gate::forUser($user)->authorize(
            'view',
            $reviewResult,
        );
        $reviewResult->load([
            'publication.contest.program',
            'officialNotification',
        ]);

        return view('candidate.application-review-results.show', [
            'result' => $reviewResult,
        ]);
    }
}
