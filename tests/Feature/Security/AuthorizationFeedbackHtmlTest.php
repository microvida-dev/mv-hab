<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorizationFeedbackHtmlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);

        Route::middleware(['web', 'auth', 'permission:applications.view'])
            ->get('/__tests/access-feedback/html', fn (): string => 'allowed')
            ->name('tests.access-feedback.html');

        Route::middleware(['web', 'auth'])
            ->get(
                '/__tests/access-feedback/origin',
                fn (): string => Blade::render('<x-flash-message />'),
            )
            ->name('tests.access-feedback.origin');

        Route::middleware(['web', 'auth', 'permission:applications.update'])
            ->post('/__tests/access-feedback/mutation', function (Request $request): string {
                $user = $request->user();

                if ($user instanceof User) {
                    $user->update(['name' => 'Alteração indevida']);
                }

                if ($request->hasFile('document')) {
                    $request->file('document')?->store('denied', 'local');
                }

                return 'mutated';
            })
            ->name('tests.access-feedback.mutation');

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_html_get_keeps_403_and_renders_integrated_safe_feedback(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)
            ->withSession(['workflow_marker' => 'preserved'])
            ->get(route('tests.access-feedback.html'));

        $response
            ->assertForbidden()
            ->assertSeeText('Acesso não autorizado')
            ->assertSeeText('Não tem permissão para realizar esta ação.')
            ->assertSeeText('A operação foi recusada sem alterar qualquer dado.')
            ->assertDontSee('applications.view')
            ->assertSessionHas('workflow_marker', 'preserved')
            ->assertHeader('X-Request-ID');

        $this->assertNotSame('', $response->headers->get('X-Request-ID'));
    }

    public function test_denied_html_mutation_uses_303_safe_referer_and_visible_flash(): void
    {
        Storage::fake('local');
        $user = User::factory()->create([
            'name' => 'Nome preservado',
            'status' => 'active',
        ]);
        $origin = route('tests.access-feedback.origin');

        $response = $this->actingAs($user)
            ->from($origin)
            ->post(route('tests.access-feedback.mutation'), [
                'name' => 'Alteração indevida',
                'document' => UploadedFile::fake()->create('privado.pdf', 10, 'application/pdf'),
            ]);

        $response
            ->assertStatus(303)
            ->assertRedirect($origin)
            ->assertSessionHas(
                'warning',
                'Não tem permissão para realizar esta ação. A operação não foi executada.',
            )
            ->assertSessionMissing('_old_input');

        $this->assertSame('Nome preservado', $user->refresh()->name);
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->get($origin)
            ->assertOk()
            ->assertSeeText('A operação não foi executada.')
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="assertive"', false);
    }

    public function test_external_same_route_and_missing_referers_render_a_safe_403_fallback(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $mutation = route('tests.access-feedback.mutation');

        $this->actingAs($user)
            ->withHeader('Referer', 'https://external.example/unsafe')
            ->post($mutation)
            ->assertForbidden()
            ->assertSeeText('Não tem permissão para realizar esta ação.')
            ->assertSee(route('profile.edit'), false);

        $this->withHeader('Referer', $mutation)
            ->post($mutation)
            ->assertForbidden()
            ->assertSeeText('Não tem permissão para realizar esta ação.');

        $this->post($mutation)
            ->assertForbidden()
            ->assertSeeText('Não tem permissão para realizar esta ação.');
    }
}
