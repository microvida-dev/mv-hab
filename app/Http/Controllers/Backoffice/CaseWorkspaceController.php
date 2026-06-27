<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Cases\CaseWorkspaceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CaseWorkspaceController extends Controller
{
    public function __construct(private readonly CaseWorkspaceService $cases) {}

    /**
     * @throws AuthorizationException
     */
    public function application(Request $request, Application $application): View
    {
        $workspace = $this->cases->forApplication(
            $this->authenticatedUser($request),
            $application,
            $request->query('q'),
        );

        return view('cases.application.show', [
            'application' => $application,
            'workspace' => $workspace,
        ]);
    }
}
