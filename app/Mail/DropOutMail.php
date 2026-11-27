<?php

namespace App\Mail;

use App\Models\CheckIn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "<pet> has been picked up" — sent when staff check the pet out.
 */
class DropOutMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CheckIn $checkIn) {}

    public function envelope(): Envelope
    {
        $petName = $this->checkIn->pet->name ?? 'Your pet';

        return new Envelope(
            subject: "Thank you for visiting — {$petName} — Pet Lodge & Spa",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.drop-out',
            with: [
                'checkIn' => $this->checkIn,
                'pet' => $this->checkIn->pet,
                'owner' => $this->checkIn->user,
            ],
        );
    }
}
