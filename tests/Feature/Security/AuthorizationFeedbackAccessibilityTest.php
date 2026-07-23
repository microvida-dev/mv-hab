<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthorizationFeedbackAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])
            ->get('/__tests/access-feedback/accessibility', function (): never {
                abort(403, 'technical exception message');
            })
            ->name('tests.access-feedback.accessibility');

        Route::middleware('web')
            ->get('/__tests/access-feedback/accessibility-guest', function (): never {
                abort(403, 'technical guest exception message');
            })
            ->name('tests.access-feedback.accessibility-guest');

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_integrated_denial_page_has_accessible_semantics_and_safe_navigation(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('tests.access-feedback.accessibility'))
            ->assertForbidden()
            ->assertSee('<h1', false)
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="assertive"', false)
            ->assertSee('aria-atomic="true"', false)
            ->assertSeeText('Não tem permissão para realizar esta ação.')
            ->assertSeeText('referência')
            ->assertSee('class="mv-button-primary"', false)
            ->assertDontSee('technical exception message')
            ->assertDontSee('Stack trace');
    }

    public function test_guest_denial_uses_public_layout_without_authenticated_navigation(): void
    {
        $this->get(route('tests.access-feedback.accessibility-guest'))
            ->assertForbidden()
            ->assertSeeText('Acesso não autorizado')
            ->assertSeeText('Não tem permissão para realizar esta ação.')
            ->assertSeeText('Ir para o Portal Público')
            ->assertSee('role="alert"', false)
            ->assertDontSee('technical guest exception message');
    }
}
