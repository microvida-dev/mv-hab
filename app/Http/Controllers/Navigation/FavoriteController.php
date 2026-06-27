<?php

namespace App\Http\Controllers\Navigation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Navigation\StoreNavigationFavoriteRequest;
use App\Models\NavigationFavorite;
use App\Services\Navigation\FavoritesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function store(StoreNavigationFavoriteRequest $request, FavoritesService $favorites): RedirectResponse
    {
        $validated = $request->validated();

        $favorites->favoriteWorkspace(
            $this->authenticatedUser($request),
            (string) $validated['workspace_key'],
        );

        return back()->with('success', 'Favorito atualizado.');
    }

    public function destroy(Request $request, NavigationFavorite $navigationFavorite, FavoritesService $favorites): RedirectResponse
    {
        $favorites->remove($this->authenticatedUser($request), $navigationFavorite);

        return back()->with('success', 'Favorito removido.');
    }
}
