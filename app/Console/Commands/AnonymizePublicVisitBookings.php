<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Visits\PublicVisitBookingRetentionService;
use Illuminate\Console\Command;

class AnonymizePublicVisitBookings extends Command
{
    protected $signature = 'public-visits:anonymize
        {--limit=500 : Número máximo de marcações a tratar}
        {--dry-run : Apenas apresentar o total elegível}';

    protected $description = 'Anonimiza marcações públicas de visita cujo prazo de retenção terminou.';

    public function handle(
        PublicVisitBookingRetentionService $retention,
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $due = $retention->dueCount();

        if ((bool) $this->option('dry-run')) {
            $this->info("Marcações elegíveis: {$due}");

            return self::SUCCESS;
        }

        $affected = $retention->anonymizeDue($limit);
        $this->info("Marcações anonimizadas: {$affected}");

        return self::SUCCESS;
    }
}
