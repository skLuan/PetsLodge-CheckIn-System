<?php

namespace App\Listeners;

use App\Events\CheckInCompleted;
use App\Mail\CheckInConfirmationMail;
use App\Models\CheckIn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends ONE booking confirmation per submission, however many pets it covers.
 *
 * A submission with three pets fires CheckInCompleted three times (steps 2-5
 * loop per pet in SubmissionManager.js). Rather than emailing the owner three
 * times, the job runs after a short delay — by which point every pet's row
 * exists — claims all of that owner's un-announced check-ins, and sends a
 * single email listing them. Whichever sibling job runs first wins the batch;
 * the others find nothing left to claim and quietly exit.
 */
class SendCheckInConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    public $tries = 3;

    public $backoff = 30;

    /**
     * How long to wait for the rest of a multi-pet submission to land before
     * announcing the batch.
     */
    private const COALESCE_DELAY_SECONDS = 120;

    /**
     * How far back to look for siblings of the triggering check-in. Wide enough
     * for a slow submission, narrow enough never to sweep up an unrelated
     * older booking whose email failed.
     */
    private const BATCH_WINDOW_MINUTES = 30;

    public function withDelay(CheckInCompleted $event): int
    {
        return self::COALESCE_DELAY_SECONDS;
    }

    public function handle(CheckInCompleted $event): void
    {
        $owner = $event->checkIn->user;
        $email = $owner->email ?? null;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('SendCheckInConfirmation: skipped, owner has no valid email', [
                'check_in_id' => $event->checkIn->id,
                'user_id' => $owner->id ?? null,
            ]);

            return;
        }

        $batch = $this->claimBatch($event->checkIn);

        if ($batch->isEmpty()) {
            // A sibling job already announced this submission.
            return;
        }

        Mail::to($email)->queue(new CheckInConfirmationMail($batch));

        Log::info('SendCheckInConfirmation: confirmation queued', [
            'user_id' => $owner->id,
            'check_in_ids' => $batch->pluck('id')->all(),
        ]);
    }

    /**
     * Atomically take ownership of every un-announced check-in belonging to the
     * same owner and submission window.
     *
     * Each row is claimed with its own conditional UPDATE, so if two sibling
     * jobs ever run concurrently each row lands in exactly one email.
     *
     * @return Collection<int, CheckIn>
     */
    private function claimBatch(CheckIn $trigger): Collection
    {
        $windowStart = $trigger->created_at
            ? $trigger->created_at->copy()->subMinutes(self::BATCH_WINDOW_MINUTES)
            : now()->subMinutes(self::BATCH_WINDOW_MINUTES);

        $candidates = CheckIn::query()
            ->where('user_id', $trigger->user_id)
            ->whereNull('confirmation_sent_at')
            ->where('created_at', '>=', $windowStart)
            ->orderBy('id')
            ->get();

        $claimed = $candidates->filter(function (CheckIn $checkIn) {
            return CheckIn::where('id', $checkIn->id)
                ->whereNull('confirmation_sent_at')
                ->update(['confirmation_sent_at' => now()]) === 1;
        })->values();

        return $claimed->isEmpty()
            ? $claimed
            : CheckIn::with(['pet.kindOfPet', 'user', 'extraServices'])
                ->whereIn('id', $claimed->pluck('id'))
                ->orderBy('id')
                ->get();
    }
}
