<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_displays_the_public_portal(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Arrendamento Acessível');
    }
}
