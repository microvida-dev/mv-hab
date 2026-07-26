<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\FutureApplicationDataReuse;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FutureApplicationDataReuseController extends Controller
{
    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', FutureApplicationDataReuse::class);

        return view('backoffice.data-reuse.index', [
            'reuses' => $this->municipalScope
                ->futureApplicationDataReuse(
                    FutureApplicationDataReuse::query(),
                    $this->authenticatedUser($request),
                )
                ->with(['user', 'sourceApplication', 'targetApplication'])
                ->latest()
                ->paginate(20),
        ]);
    }
}
