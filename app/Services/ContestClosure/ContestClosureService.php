<?php

namespace App\Services\ContestClosure;

use App\Enums\ContestClosureStatus;
use App\Models\Contest;
use App\Models\ContestClosure;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class ContestClosureService
{
    public function __construct(
        private readonly ContestClosureValidator $validator,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function close(Contest $contest, User $actor, array $data = []): ContestClosure
    {
        $pending = $this->validator->criticalPendingItems($contest);
        $allowPending = (bool) ($data['allow_pending'] ?? false);

        if ($pending !== [] && ! $allowPending) {
            throw ValidationException::withMessages(['contest_id' => implode(' ', $pending)]);
        }

        $closure = ContestClosure::query()->firstOrNew(['contest_id' => $contest->id]);

        $closure->fill([
            'summary' => [
                'lottery_draws' => $contest->lotteryDraws()->count(),
                'allocations' => $contest->allocations()->count(),
                'closed_with_pending_items' => $pending !== [],
            ],
            'critical_pending_items' => $pending,
            'snapshot' => [
                'contest_id' => $contest->id,
                'contest_title' => $contest->title,
                'closed_at' => now()->toISOString(),
            ],
            'notes' => $data['notes'] ?? null,
        ]);

        $closure->forceFill([
            'closure_number' => $closure->closure_number ?? sprintf('FC-%s-%06d', now()->format('Y'), ContestClosure::query()->count() + 1),
            'status' => ContestClosureStatus::Closed,
            'closed_at' => now(),
            'closed_by' => $actor->id,
        ])->save();

        $this->audit->record(AuditEvents::APPROVE, $closure, 'allocations', 'contest_close', 'Concurso fechado.');

        return $closure->refresh();
    }
}
