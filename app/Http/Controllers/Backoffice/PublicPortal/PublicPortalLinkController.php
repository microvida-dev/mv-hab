<?php

namespace App\Http\Controllers\Backoffice\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\PublicPortal\StorePublicPortalLinkRequest;
use App\Http\Requests\Backoffice\PublicPortal\UpdatePublicPortalLinkRequest;
use App\Models\PublicPortalLink;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PublicPortalLinkController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', PublicPortalLink::class);

        return view('backoffice.public-portal.links.index', [
            'links' => PublicPortalLink::query()->orderBy('sort_order')->orderBy('label')->paginate(20),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', PublicPortalLink::class);

        return view('backoffice.public-portal.links.create', ['link' => new PublicPortalLink]);
    }

    public function store(StorePublicPortalLinkRequest $request): RedirectResponse
    {
        Gate::authorize('create', PublicPortalLink::class);

        PublicPortalLink::query()->create($this->normalizeBooleans($request->validated(), $request));

        return to_route('backoffice.public-portal.links.index')->with('success', 'Ligação pública criada.');
    }

    public function edit(PublicPortalLink $link): View
    {
        Gate::authorize('update', $link);

        return view('backoffice.public-portal.links.edit', compact('link'));
    }

    public function update(UpdatePublicPortalLinkRequest $request, PublicPortalLink $link): RedirectResponse
    {
        Gate::authorize('update', $link);

        $link->update($this->normalizeBooleans($request->validated(), $request));

        return to_route('backoffice.public-portal.links.index')->with('success', 'Ligação pública atualizada.');
    }

    public function destroy(PublicPortalLink $link): RedirectResponse
    {
        Gate::authorize('delete', $link);

        $link->delete();

        return back()->with('success', 'Ligação pública removida.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeBooleans(array $data, StorePublicPortalLinkRequest $request): array
    {
        $data['opens_new_tab'] = $request->boolean('opens_new_tab');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
