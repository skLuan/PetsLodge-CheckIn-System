<?php

namespace App\Listeners;

use App\Events\PetDroppedOut;
use App\Mail\DropOutMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Thanks the owner once their pet has been picked up.
 */
class SendDropOutNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public $backoff = 30;

    public function handle(PetDroppedOut $event): void
    {
        $email = $event->checkIn->user->email ?? null;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('SendDropOutNotification: skipped, owner has no valid email', [
                'check_in_id' => $event->checkIn->id,
            ]);

            return;
        }

        $checkIn = $event->checkIn->loadMissing(['pet.kindOfPet', 'user']);

        Mail::to($email)->queue(new DropOutMail($checkIn));
    }
}
