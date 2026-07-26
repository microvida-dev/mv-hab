<?php

namespace App\Http\Controllers\Backoffice\Security;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompletePermissionReviewRequest;
use App\Http\Requests\StorePermissionReviewRequest;
use App\Models\PermissionReview;
use App\Services\Security\PermissionReviewService;
use App\Services\Security\SecurityMunicipalScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PermissionReviewController extends Controller
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function index(Request $request): View
    {
        $actor = $this->authenticatedUser($request);
        Gate::authorize('viewAny', PermissionReview::class);

        return view('backoffice.security.permission-reviews.index', [
            'reviews' => $this->scope
                ->permissionReviews(PermissionReview::query(), $actor)
                ->with('startedBy')
                ->latest('started_at')
                ->paginate(20),
        ]);
    }

    public function store(StorePermissionReviewRequest $request, PermissionReviewService $reviews): RedirectResponse
    {
        $review = $reviews->create($this->authenticatedUser($request), $request->validated('scope') ?: 'all');

        return redirect()->route('backoffice.security.permission-reviews.show', $review)->with('status', 'Revisão de permissões criada.');
    }

    public function show(Request $request, PermissionReview $permissionReview): View
    {
        Gate::authorize('view', $permissionReview);
        $permissionReview->load('items.user', 'startedBy', 'completedBy');

        return view('backoffice.security.permission-reviews.show', ['review' => $permissionReview]);
    }

    public function complete(CompletePermissionReviewRequest $request, PermissionReview $permissionReview, PermissionReviewService $reviews): RedirectResponse
    {
        Gate::authorize('complete', $permissionReview);
        $reviews->complete($permissionReview, $this->authenticatedUser($request), $request->validated('summary'));

        return back()->with('status', 'Revisão de permissões concluída.');
    }
}
