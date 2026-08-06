<?php

namespace App\Events;

use App\Models\CheckIn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when staff mark a pet as dropped in (the pet is now at the lodge).
 */
class PetDroppedIn
{
    use Dispatchable, SerializesModels;

    public function __construct(public CheckIn $checkIn) {}
}
