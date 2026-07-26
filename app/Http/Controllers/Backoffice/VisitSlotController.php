<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\VisitSlotStatus;
use App\Http\Controllers\Controller;
use App\Models\VisitSlot;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Municipalities\VisitMunicipalContextService;
use App\Services\Visits\VisitAuditService;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VisitSlotController extends Controller
{
    public function __construct(
        private readonly VisitAuditService $audit,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly VisitMunicipalContextService $municipalContext,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', VisitSlot::class);

        return view('backoffice.visit-slots.index', [
            'slots' => $this->municipalScope
                ->visitSlots(
                    VisitSlot::query(),
                    $this->authenticatedUser($request),
                )
                ->with([
                    'availability',
                    'contest',
                    'housingUnit',
                    'staff',
                ])
                ->orderBy('starts_at')
                ->paginate(20),
        ]);
    }

    public function block(
        Request $request,
        VisitSlot $visitSlot,
    ): RedirectResponse {
        Gate::authorize('blockBackoffice', $visitSlot);

        DB::transaction(function () use ($request, $visitSlot): void {
            $visitSlot = VisitSlot::query()
                ->whereKey($visitSlot)
                ->lockForUpdate()
                ->firstOrFail();
            $this->municipalContext->validateSlotForActor(
                $visitSlot,
                $this->authenticatedUser($request),
            );
            $visitSlot->forceFill([
                'status' => VisitSlotStatus::Blocked,
            ])->save();
            $this->audit->slot(
                AuditEvents::UPDATE,
                $visitSlot,
                'Slot de visita bloqueado.',
            );
        });

        return back()->with('success', 'Slot bloqueado.');
    }

    public function unblock(
        Request $request,
        VisitSlot $visitSlot,
    ): RedirectResponse {
        Gate::authorize('unblockBackoffice', $visitSlot);

        DB::transaction(function () use ($request, $visitSlot): void {
            $visitSlot = VisitSlot::query()
                ->whereKey($visitSlot)
                ->lockForUpdate()
                ->firstOrFail();
            $this->municipalContext->validateSlotForActor(
                $visitSlot,
                $this->authenticatedUser($request),
            );
            $visitSlot->forceFill([
                'status' => (int) $visitSlot->booked_count
                    >= (int) $visitSlot->capacity
                        ? VisitSlotStatus::Full
                        : VisitSlotStatus::Available,
            ])->save();
            $this->audit->slot(
                AuditEvents::UPDATE,
                $visitSlot,
                'Slot de visita desbloqueado.',
            );
        });

        return back()->with('success', 'Slot desbloqueado.');
    }
}
