<?php

namespace App\Listeners;

use App\Events\PetDroppedIn;
use App\Mail\DropInMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells the owner their pet arrived safely at the lodge.
 */
class SendDropInNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public $backoff = 30;

    public function handle(PetDroppedIn $event): void
    {
        $email = $event->checkIn->user->email ?? null;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('SendDropInNotification: skipped, owner has no valid email', [
                'check_in_id' => $event->checkIn->id,
            ]);

            return;
        }

        $checkIn = $event->checkIn->loadMissing(['pet.kindOfPet', 'user']);

        Mail::to($email)->queue(new DropInMail($checkIn));
    }
}
