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
}
