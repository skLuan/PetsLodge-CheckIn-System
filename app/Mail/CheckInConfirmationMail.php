<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * "Your booking is confirmed" — sent once per submission.
 *
 * Takes the whole batch of check-ins (one per pet) so an owner booking three
 * pets receives a single email listing all three, not three near-identical ones.
 */
class CheckInConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param Collection<int, \App\Models\CheckIn> $checkIns */
    public function __construct(public Collection $checkIns) {}

    public function envelope(): Envelope
    {
        $petNames = $this->checkIns
            ->map(fn ($checkIn) => $checkIn->pet->name ?? null)
            ->filter()
            ->values();

        // "Luna", "Luna & Rocky", "Luna, Rocky & Milo"
        $subjectPets = $petNames->count() > 1
            ? $petNames->slice(0, -1)->implode(', ').' & '.$petNames->last()
            : $petNames->first();

        return new Envelope(
            subject: $subjectPets
                ? "Booking confirmed for {$subjectPets} — Pet Lodge & Spa"
                : 'Your booking is confirmed — Pet Lodge & Spa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.check-in-confirmation',
            with: [
                'checkIns' => $this->checkIns,
                'owner' => $this->checkIns->first()?->user,
            ],
        );
    }
}
