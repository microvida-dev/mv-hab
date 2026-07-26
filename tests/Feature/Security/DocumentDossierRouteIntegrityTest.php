<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DocumentDossierRouteIntegrityTest extends TestCase
{
    public function test_document_dossier_routes_only_reference_existing_controller_actions(): void
    {
        $routeNames = [
            'backoffice.applications.document-dossier.show',
            'backoffice.applications.document-dossier.generate',
            'backoffice.document-dossiers.download',
        ];

        foreach ($routeNames as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");

            $action = $route->getActionName();

            $this->assertStringContainsString('@', $action);

            [$controller, $method] = explode('@', $action, 2);

            $this->assertTrue(
                method_exists($controller, $method),
                "Route [{$routeName}] references missing action [{$action}].",
            );
        }
    }

    public function test_obsolete_document_dossier_update_route_is_not_registered(): void
    {
        $this->assertNull(
            Route::getRoutes()->getByName(
                'backoffice.document-dossiers.update',
            ),
        );
    }
}
