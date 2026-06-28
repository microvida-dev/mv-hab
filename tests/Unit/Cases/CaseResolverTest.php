<?php

namespace Tests\Unit\Cases;

use App\Models\Contest;
use App\Services\Cases\CaseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_finds_case_by_route_key(): void
    {
        $contest = Contest::factory()->open()->create();

        $resolved = app(CaseResolver::class)->resolve('contest', $contest->getRouteKey());

        $this->assertTrue($contest->is($resolved));
    }
}
