<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Platform\ActivatePlatformMunicipalContextRequest;
use App\Http\Requests\Backoffice\Platform\ClearPlatformMunicipalContextRequest;
use App\Http\Requests\Backoffice\Platform\ListPlatformMunicipalContextRequest;
use App\Models\Municipality;
use App\Services\Platform\PlatformMunicipalContextService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

final class PlatformMunicipalContextController extends Controller
{
    public function index(
        ListPlatformMunicipalContextRequest $request,
        PlatformMunicipalContextService $contexts,
    ): View {
        $actor = $this->authenticatedUser($request);
        $validated = $request->validated();
        $search = $validated['q'] ?? null;
        $status = (string) ($validated['status'] ?? 'all');
        $currentMunicipality = $contexts->currentMunicipality($actor);

        $municipalities = Municipality::query()
            ->select([
                'id',
                'name',
                'code',
                'tax_number',
                'active',
            ])
            ->when(
                is_string($search) && $search !== '',
                function (Builder $query) use ($search): void {
                    $pattern = '%'.$search.'%';

                    $query->where(function (Builder $searchQuery) use ($pattern): void {
                        $searchQuery
                            ->where('name', 'like', $pattern)
                            ->orWhere('code', 'like', $pattern)
                            ->orWhere('tax_number', 'like', $pattern);
                    });
                },
            )
            ->when(
                $status === 'active',
                fn (Builder $query): Builder => $query->where('active', true),
            )
            ->when(
                $status === 'inactive',
                fn (Builder $query): Builder => $query->where('active', false),
            )
            ->orderByDesc('active')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('backoffice.platform.municipal-context.index', [
            'municipalities' => $municipalities,
            'currentMunicipality' => $currentMunicipality,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function store(
        ActivatePlatformMunicipalContextRequest $request,
        PlatformMunicipalContextService $contexts,
    ): RedirectResponse {
        $municipality = Municipality::query()->findOrFail(
            $request->integer('municipality_id'),
        );

        $contexts->activate(
            $this->authenticatedUser($request),
            $municipality,
            (string) $request->validated('justification'),
        );

        return to_route('backoffice.platform.municipal-context.index')
            ->with(
                'status',
                'Contexto municipal ativado para '.$municipality->name.'.',
            );
    }

    public function destroy(
        ClearPlatformMunicipalContextRequest $request,
        PlatformMunicipalContextService $contexts,
    ): RedirectResponse {
        $contexts->clear(
            $this->authenticatedUser($request),
            (string) $request->validated('justification'),
        );

        return to_route('backoffice.platform.municipal-context.index')
            ->with('status', 'Contexto municipal encerrado.');
    }
}
