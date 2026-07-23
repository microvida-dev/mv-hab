<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizationFeedbackJsonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);

        Route::middleware(['web', 'auth', 'permission:applications.view'])
            ->get('/__tests/access-feedback/json', fn (): array => ['allowed' => true])
            ->name('tests.access-feedback.json');

        Route::middleware(['web', 'auth', 'permission:applications.update'])
            ->post('/__tests/access-feedback/json-mutation', function (Request $request): array {
                $user = $request->user();

                if ($user instanceof User) {
                    $user->update(['name' => 'Alteração indevida']);
                }

                return ['mutated' => true];
            })
            ->name('tests.access-feedback.json-mutation');

        Route::middleware(['web', 'auth'])
            ->get('/__tests/access-feedback/json-hidden', function (): never {
                abort(404);
            })
            ->name('tests.access-feedback.json-hidden');

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_json_denial_keeps_403_without_redirect_or_internal_details(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)
            ->withHeader('X-Request-ID', 'client-controlled-id')
            ->getJson(route('tests.access-feedback.json'));

        $response
            ->assertForbidden()
            ->assertHeader('content-type', 'application/json')
            ->assertJson([
                'message' => 'Não tem permissão para realizar esta ação.',
                'code' => 'access_denied',
            ])
            ->assertJsonStructure(['message', 'code', 'request_id'])
            ->assertDontSee('applications.view');

        $requestId = (string) $response->json('request_id');

        $this->assertTrue(Str::isUuid($requestId));
        $this->assertSame($requestId, $response->headers->get('X-Request-ID'));
        $this->assertNotSame('client-controlled-id', $requestId);
        $this->assertFalse($response->isRedirect());
    }

    public function test_json_mutation_is_denied_without_changing_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Nome preservado',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson(route('tests.access-feedback.json-mutation'), [
                'name' => 'Alteração indevida',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'access_denied')
            ->assertJsonMissing(['mutated' => true]);

        $this->assertSame('Nome preservado', $user->refresh()->name);
    }

    public function test_json_preserves_authentication_and_hidden_resource_statuses(): void
    {
        $this->getJson(route('tests.access-feedback.json'))
            ->assertUnauthorized()
            ->assertJsonMissing(['code' => 'access_denied']);

        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->getJson(route('tests.access-feedback.json-hidden'))
            ->assertNotFound()
            ->assertJsonMissing(['code' => 'access_denied']);
    }
}
