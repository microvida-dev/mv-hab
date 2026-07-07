<?php

namespace App\Http\Controllers\Navigation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Navigation\UpdateWorkspacePreferenceRequest;
use App\Services\Navigation\WorkspacePreferenceService;
use Illuminate\Http\RedirectResponse;

class WorkspacePreferenceController extends Controller
{
    public function update(
        UpdateWorkspacePreferenceRequest $request,
        WorkspacePreferenceService $preferences,
    ): RedirectResponse {
        $preferences->update(
            $this->authenticatedUser($request),
            $request->validated(),
        );

        return back()->with('success', 'Preferências do espaço de trabalho atualizadas.');
    }
}
