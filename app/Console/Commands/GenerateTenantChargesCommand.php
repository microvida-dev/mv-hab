<?php

namespace App\Console\Commands;

use App\Enums\ChargeType;
use App\Models\User;
use App\Services\TenantBilling\TenantChargeRunService;
use Illuminate\Console\Command;

class GenerateTenantChargesCommand extends Command
{
    protected $signature = 'tenants:generate-charges {--year=} {--month=} {--type=rent} {--actor=}';

    protected $description = 'Generate operational tenant charges for a billing period without external banking movement.';

    public function handle(TenantChargeRunService $chargeRuns): int
    {
        $actor = User::query()->find($this->option('actor')) ?? User::query()->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))->first();

        if (! $actor) {
            $this->error('No actor user available to audit the charge run.');

            return self::FAILURE;
        }

        $run = $chargeRuns->run(
            $actor,
            (int) ($this->option('year') ?: now()->year),
            (int) ($this->option('month') ?: now()->month),
            ChargeType::from((string) $this->option('type')),
        );

        $this->info("Charge run {$run->run_number} completed with {$run->generated_count} generated and {$run->skipped_count} skipped.");

        return self::SUCCESS;
    }
}
