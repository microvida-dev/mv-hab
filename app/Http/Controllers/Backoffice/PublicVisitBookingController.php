<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Enums\PublicVisitBookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\UpdatePublicVisitBookingStatusRequest;
use App\Models\PublicVisitBooking;
use App\Services\Visits\PublicVisitBookingAuditService;
use App\Services\Visits\PublicVisitBookingMunicipalScopeService;
use App\Services\Visits\PublicVisitBookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PublicVisitBookingController extends Controller
{
    public function __construct(
        private readonly PublicVisitBookingService $bookings,
        private readonly PublicVisitBookingMunicipalScopeService $scope,
        private readonly PublicVisitBookingAuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize(
            'viewAnyBackoffice',
            PublicVisitBooking::class,
        );
        $user = $this->authenticatedUser($request);
        $status = PublicVisitBookingStatus::tryFrom(
            $request->string('status')->toString(),
        );
        $query = $this->scope->query(
            PublicVisitBooking::query(),
            $user,
        );

        if ($status instanceof PublicVisitBookingStatus) {
            $query->where('status', $status->value);
        }

        return view('backoffice.public-visit-bookings.index', [
            'bookings' => $query
                ->with(['slot', 'housingUnit', 'contest'])
                ->latest('booked_at')
                ->paginate(20)
                ->withQueryString(),
            'status' => $status->value ?? '',
            'statusOptions' => PublicVisitBookingStatus::cases(),
        ]);
    }

    public function show(
        Request $request,
        PublicVisitBooking $publicVisitBooking,
    ): View {
        Gate::authorize('viewBackoffice', $publicVisitBooking);
        $publicVisitBooking->load([
            'slot.availability',
            'housingUnit',
            'contest',
            'municipality',
            'statusChangedBy',
        ]);

        $this->audit->record(
            'public_visit_booking_sensitive_viewed',
            $publicVisitBooking,
            'Dados de contacto da marcação pública consultados no backoffice.',
            ['action' => 'view'],
            $this->authenticatedUser($request),
        );

        return view('backoffice.public-visit-bookings.show', [
            'booking' => $publicVisitBooking,
        ]);
    }

    public function cancel(
        UpdatePublicVisitBookingStatusRequest $request,
        PublicVisitBooking $publicVisitBooking,
    ): RedirectResponse {
        $data = $request->validated();
        $booking = $this->bookings->cancelBackoffice(
            $publicVisitBooking,
            $this->authenticatedUser($request),
            $data['notes'] ?? null,
        );

        return to_route(
            'backoffice.public-visit-bookings.show',
            $booking,
        )->with('success', 'Marcação pública cancelada.');
    }

    public function attended(
        UpdatePublicVisitBookingStatusRequest $request,
        PublicVisitBooking $publicVisitBooking,
    ): RedirectResponse {
        $data = $request->validated();
        $booking = $this->bookings->markAttended(
            $publicVisitBooking,
            $this->authenticatedUser($request),
            $data['notes'] ?? null,
        );

        return to_route(
            'backoffice.public-visit-bookings.show',
            $booking,
        )->with('success', 'Comparência registada.');
    }

    public function noShow(
        UpdatePublicVisitBookingStatusRequest $request,
        PublicVisitBooking $publicVisitBooking,
    ): RedirectResponse {
        $data = $request->validated();
        $booking = $this->bookings->markNoShow(
            $publicVisitBooking,
            $this->authenticatedUser($request),
            $data['notes'] ?? null,
        );

        return to_route(
            'backoffice.public-visit-bookings.show',
            $booking,
        )->with('success', 'Falta de comparência registada.');
    }
}
