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
 * "We've got <pet>" — sent when staff mark the pet as dropped in at the lodge.
 */
class DropInMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public CheckIn $checkIn) {}

    public function envelope(): Envelope
    {
        $petName = $this->checkIn->pet->name ?? 'your pet';

        return new Envelope(
            subject: "{$petName} has arrived safely — Pet Lodge & Spa",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.drop-in',
            with: [
                'checkIn' => $this->checkIn,
                'pet' => $this->checkIn->pet,
                'owner' => $this->checkIn->user,
            ],
        );
    }
}
