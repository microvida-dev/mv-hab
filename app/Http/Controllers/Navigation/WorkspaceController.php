<?php

namespace App\Http\Controllers\Navigation;

use App\Http\Controllers\Controller;
use App\Services\Navigation\FavoritesService;
use App\Services\Navigation\RecentItemsService;
use App\Services\Navigation\WorkspaceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function __invoke(
        string $workspace,
        Request $request,
        WorkspaceService $workspaces,
        FavoritesService $favorites,
        RecentItemsService $recentItems,
    ): View {
        $user = $this->authenticatedUser($request);
        $currentWorkspace = $workspaces->authorizedWorkspace($user, $workspace);

        abort_unless(is_array($currentWorkspace), 403);

        $recentItems->recordWorkspaceVisit($user, $workspace);

        return view('navigation.workspace', [
            'workspace' => $currentWorkspace,
            'groups' => $workspaces->navigationGroups($user, $workspace),
            'favorites' => $favorites->forUser($user),
            'recentItems' => $recentItems->forUser($user),
            'quickActions' => $workspaces->quickActions($user),
        ]);
    }
}
