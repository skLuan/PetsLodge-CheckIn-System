<?php

namespace App\Events;

use App\Models\CheckIn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when staff check a pet out (the owner has picked the pet up).
 */
class PetDroppedOut
{
    use Dispatchable, SerializesModels;

    public function __construct(public CheckIn $checkIn) {}
}
