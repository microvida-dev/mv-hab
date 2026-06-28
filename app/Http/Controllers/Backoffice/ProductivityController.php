<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Productivity\ProductivityDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductivityController extends Controller
{
    public function __invoke(Request $request, ProductivityDashboardService $productivity): View
    {
        $user = $this->authenticatedUser($request);

        abort_unless($productivity->canView($user), 403);

        return view('productivity.index', [
            'productivity' => $productivity->forUser($user),
        ]);
    }
}
