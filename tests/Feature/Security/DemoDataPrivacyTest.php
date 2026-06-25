<?php

namespace Tests\Feature\Security;

use App\Models\Document;
use App\Models\User;
use Database\Seeders\MunicipalPilotStagingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_uses_reserved_domains_and_does_not_create_real_files(): void
    {
        $this->seed(MunicipalPilotStagingSeeder::class);

        $unexpectedEmails = User::query()
            ->whereNot('email', 'like', '%@example.test')
            ->whereNot('email', 'like', '%@exemplo.pt')
            ->count();

        $this->assertSame(0, $unexpectedEmails);
        $this->assertSame(0, Document::query()->whereNotNull('path')->count());
        $this->assertDirectoryDoesNotExist(storage_path('app/private/demo-real-documents'));
    }
}
