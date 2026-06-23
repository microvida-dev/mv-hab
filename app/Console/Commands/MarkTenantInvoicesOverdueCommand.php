<?php

namespace App\Console\Commands;

use App\Enums\TenantInvoiceStatus;
use App\Models\TenantInvoice;
use Illuminate\Console\Command;

class MarkTenantInvoicesOverdueCommand extends Command
{
    protected $signature = 'tenants:mark-overdue-invoices';

    protected $description = 'Mark issued tenant invoices as overdue when their due date is past.';

    public function handle(): int
    {
        $count = TenantInvoice::query()
            ->whereIn('status', [
                TenantInvoiceStatus::Issued->value,
                TenantInvoiceStatus::Sent->value,
                TenantInvoiceStatus::PartiallyPaid->value,
            ])
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('amount_outstanding', '>', 0)
            ->update(['status' => TenantInvoiceStatus::Overdue->value]);

        $this->info("Updated {$count} tenant invoices to overdue.");

        return self::SUCCESS;
    }
}
