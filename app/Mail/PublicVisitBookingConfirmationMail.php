<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PublicVisitBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PublicVisitBookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PublicVisitBooking $booking,
        public readonly string $cancellationToken,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmação da visita ao fogo municipal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.public-visit-booking-confirmation',
            with: [
                'cancellationUrl' => route(
                    'public.visit-bookings.cancel',
                    ['token' => $this->cancellationToken],
                ),
            ],
        );
    }
}
