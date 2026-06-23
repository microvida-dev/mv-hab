<?php

namespace App\Http\Controllers\Backoffice\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\PublicPortal\UpdatePublicPortalSettingsRequest;
use App\Models\PublicPortalSetting;
use App\Services\PublicPortal\PublicPortalSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PublicPortalSettingController extends Controller
{
    public function edit(PublicPortalSettingsService $settingsService): View
    {
        Gate::authorize('viewAny', PublicPortalSetting::class);

        return view('backoffice.public-portal.settings.edit', [
            'settings' => $settingsService->all(),
            'records' => $settingsService->editableSettings(),
        ]);
    }

    public function update(UpdatePublicPortalSettingsRequest $request, PublicPortalSettingsService $settingsService): RedirectResponse
    {
        Gate::authorize('updateAny', PublicPortalSetting::class);

        $settingsService->updateMany($request->settings());

        return back()->with('success', 'Configurações públicas atualizadas.');
    }
}
