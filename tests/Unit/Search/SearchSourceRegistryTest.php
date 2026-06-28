<?php

namespace Tests\Unit\Search;

use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchSourceRegistry;
use Tests\TestCase;

class SearchSourceRegistryTest extends TestCase
{
    public function test_registry_exposes_universal_search_sources_and_command_source(): void
    {
        $registry = $this->app->make(SearchSourceRegistry::class);

        $sources = $registry->sources();

        $this->assertNotEmpty($sources);
        $this->assertContainsOnlyInstancesOf(SearchSource::class, $sources);
        $this->assertSame('command', $registry->commands()->key());
        $this->assertContains('application', array_map(fn (SearchSource $source): string => $source->key(), $sources));
        $this->assertContains('workspace', array_map(fn (SearchSource $source): string => $source->key(), $sources));
    }
}
