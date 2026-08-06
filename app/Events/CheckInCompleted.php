<?php

namespace App\Events;

use App\Models\CheckIn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a pet's check-in submission has finished (end of step 5).
 *
 * One event per pet: a submission covering several pets fires this several
 * times. The listener is responsible for collapsing those into a single email.
 */
class CheckInCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public CheckIn $checkIn) {}
}
