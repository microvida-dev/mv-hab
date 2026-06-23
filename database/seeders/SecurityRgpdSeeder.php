<?php

namespace Database\Seeders;

use App\Services\Security\SensitiveFieldEncryptionReviewService;
use Illuminate\Database\Seeder;

class SecurityRgpdSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ConsentPurposeSeeder::class,
            SecurityAlertRuleSeeder::class,
            RetentionPolicySeeder::class,
            SecurityChecklistSeeder::class,
        ]);

        app(SensitiveFieldEncryptionReviewService::class)->seedDefaultRegistry();
    }
}
