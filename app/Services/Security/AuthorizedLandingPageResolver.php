<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;

class AuthorizedLandingPageResolver
{
    /**
     * @return array{url: string, label: string}|null
     */
    public function resolve(Request $request): ?array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->routeTarget($request, 'public.portal', 'Ir para o Portal Público');
        }

        if ($user->hasRole('candidate')) {
            $candidateTarget = $this->routeTarget(
                $request,
                'candidate.dashboard',
                'Ir para a Área do Candidato',
            );

            if ($candidateTarget !== null) {
                return $candidateTarget;
            }
        }

        if (
            ($user->status ?? 'active') === 'active'
            && $user->hasPermission('dashboard.view')
        ) {
            $dashboardTarget = $this->routeTarget(
                $request,
                'dashboard',
                'Ir para o Painel Principal',
            );

            if ($dashboardTarget !== null) {
                return $dashboardTarget;
            }
        }

        return $this->routeTarget($request, 'profile.edit', 'Gerir a minha conta')
            ?? $this->routeTarget($request, 'public.portal', 'Ir para o Portal Público');
    }

    /**
     * @return array{url: string, label: string}|null
     */
    private function routeTarget(Request $request, string $routeName, string $label): ?array
    {
        $route = Route::getRoutes()->getByName($routeName);

        if (! $route instanceof IlluminateRoute || ! $this->supportsSafeRead($route)) {
            return null;
        }

        $url = route($routeName);

        if ($this->isCurrentRequest($request, $url)) {
            return null;
        }

        return [
            'url' => $url,
            'label' => $label,
        ];
    }

    private function supportsSafeRead(IlluminateRoute $route): bool
    {
        return in_array('GET', $route->methods(), true);
    }

    private function isCurrentRequest(Request $request, string $url): bool
    {
        $targetPath = parse_url($url, PHP_URL_PATH);

        return is_string($targetPath)
            && rtrim($targetPath, '/') === rtrim('/'.$request->path(), '/');
    }
}
