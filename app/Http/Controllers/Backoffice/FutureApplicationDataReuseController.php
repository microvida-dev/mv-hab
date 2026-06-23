<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\FutureApplicationDataReuse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class FutureApplicationDataReuseController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', FutureApplicationDataReuse::class);

        return view('backoffice.data-reuse.index', [
            'reuses' => FutureApplicationDataReuse::query()->with(['user', 'sourceApplication', 'targetApplication'])->latest()->paginate(20),
        ]);
    }
}
