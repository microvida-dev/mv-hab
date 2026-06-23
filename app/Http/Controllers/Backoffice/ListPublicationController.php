<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ListPublication;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class ListPublicationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', ListPublication::class);
        $publications = ListPublication::query()->with(['publishable', 'publishedBy'])->latest()->paginate(20);

        return view('backoffice.official-notifications.index', ['notifications' => collect(), 'publications' => $publications]);
    }
}
