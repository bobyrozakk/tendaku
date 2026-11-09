<?php

namespace App\Mail;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentalReturnReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Rental $rental) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pengingat Pengembalian Sewa [{$this->rental->booking_code}] - Tendaku",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rentals.return-reminder',
            with: [
                'rental' => $this->rental,
                'customerName' => $this->rental->customerName(),
                'returnDateTime' => $this->rental->returnDateTime(),
                'vendor' => $this->rental->vendor,
                'items' => $this->rental->details()->with('masterItem')->get(),
            ],
        );
    }
}
