<?php

namespace App\Providers;

use App\Events\CheckInCompleted;
use App\Events\PetDroppedIn;
use App\Events\PetDroppedOut;
use App\Listeners\SendCheckInConfirmation;
use App\Listeners\SendDropInNotification;
use App\Listeners\SendDropOutNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Transactional emails to clients (Plan 02).
        CheckInCompleted::class => [
            SendCheckInConfirmation::class,
        ],
        PetDroppedIn::class => [
            SendDropInNotification::class,
        ],
        PetDroppedOut::class => [
            SendDropOutNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
