<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicPortal\StorePublicVisitBookingRequest;
use App\Services\PublicPortal\PublicHousingUnitService;
use App\Services\Visits\PublicVisitBookingRateLimiter;
use App\Services\Visits\PublicVisitBookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicVisitBookingController extends Controller
{
    public function store(
        StorePublicVisitBookingRequest $request,
        string $slug,
        PublicHousingUnitService $housingUnits,
        PublicVisitBookingRateLimiter $rateLimiter,
        PublicVisitBookingService $bookings,
    ): RedirectResponse {
        $data = $request->validated();
        $rateLimiter->hit((string) $data['email'], $request->ip());
        $housingUnit = $housingUnits->findBySlug($slug);
        $result = $bookings->book($housingUnit, $data);
        $booking = $result['booking'];
        $token = $result['cancellation_token'];

        return to_route('public.visit-bookings.confirmed')
            ->with('public_visit_booking_confirmation', [
                'reference' => $booking->booking_reference,
                'housing_title' => $housingUnit->displayTitle(),
                'starts_at' => $booking->slot?->starts_at?->toISOString(),
                'guest_count' => (int) $booking->guest_count,
                'cancellation_url' => route(
                    'public.visit-bookings.cancel',
                    ['token' => $token],
                ),
            ]);
    }

    public function confirmed(Request $request): View|RedirectResponse
    {
        $confirmation = $request->session()->get(
            'public_visit_booking_confirmation',
        );

        if (! is_array($confirmation)) {
            return to_route('public.housing-offer.index');
        }

        return view('public.visit-bookings.confirmed', [
            'confirmation' => $confirmation,
        ]);
    }

    public function cancel(
        string $token,
        PublicVisitBookingService $bookings,
    ): View {
        return view('public.visit-bookings.cancel', [
            'booking' => $bookings->findByCancellationToken($token),
            'token' => $token,
        ]);
    }

    public function destroy(
        Request $request,
        string $token,
        PublicVisitBookingService $bookings,
    ): RedirectResponse {
        $bookings->cancelByToken($token);

        return to_route('public.visit-bookings.cancelled')
            ->with('success', 'A marcação foi cancelada com sucesso.');
    }

    public function cancelled(): View
    {
        return view('public.visit-bookings.cancelled');
    }
}
