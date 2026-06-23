<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\VisitSlotStatus;
use App\Http\Controllers\Controller;
use App\Models\VisitSlot;
use App\Services\Visits\VisitAuditService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class VisitSlotController extends Controller
{
    public function __construct(private readonly VisitAuditService $audit) {}

    public function index(): View
    {
        Gate::authorize('viewAny', VisitSlot::class);

        return view('backoffice.visit-slots.index', [
            'slots' => VisitSlot::query()->with(['availability', 'contest', 'housingUnit', 'staff'])->orderBy('starts_at')->paginate(20),
        ]);
    }

    public function block(VisitSlot $visitSlot): RedirectResponse
    {
        Gate::authorize('update', $visitSlot);
        $visitSlot->forceFill(['status' => VisitSlotStatus::Blocked])->save();
        $this->audit->slot(AuditEvents::UPDATE, $visitSlot, 'Slot de visita bloqueado.');

        return back()->with('success', 'Slot bloqueado.');
    }

    public function unblock(VisitSlot $visitSlot): RedirectResponse
    {
        Gate::authorize('update', $visitSlot);
        $visitSlot->forceFill([
            'status' => (int) $visitSlot->booked_count >= (int) $visitSlot->capacity
                ? VisitSlotStatus::Full
                : VisitSlotStatus::Available,
        ])->save();
        $this->audit->slot(AuditEvents::UPDATE, $visitSlot, 'Slot de visita desbloqueado.');

        return back()->with('success', 'Slot desbloqueado.');
    }
}
