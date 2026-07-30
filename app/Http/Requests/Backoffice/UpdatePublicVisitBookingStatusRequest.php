<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice;

use App\Models\PublicVisitBooking;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePublicVisitBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $booking = $this->route('publicVisitBooking');
        $ability = $this->ability();

        return $user instanceof User
            && $booking instanceof PublicVisitBooking
            && $ability !== null
            && $user->can($ability, $booking);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function ability(): ?string
    {
        return match ($this->route()?->getName()) {
            'backoffice.public-visit-bookings.cancel' => 'cancelBackoffice',
            'backoffice.public-visit-bookings.attended' => 'completeBackoffice',
            'backoffice.public-visit-bookings.no-show' => 'markNoShowBackoffice',
            default => null,
        };
    }
}
