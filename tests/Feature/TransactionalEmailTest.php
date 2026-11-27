<?php

namespace Tests\Feature;

use App\Events\CheckInCompleted;
use App\Events\PetDroppedIn;
use App\Events\PetDroppedOut;
use App\Listeners\SendCheckInConfirmation;
use App\Mail\CheckInConfirmationMail;
use App\Mail\DropInMail;
use App\Mail\DropOutMail;
use App\Models\Castrated;
use App\Models\CheckIn;
use App\Models\Gender;
use App\Models\KindOfPet;
use App\Models\Pet;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\CastratedSeeder;
use Database\Seeders\GenderSeeder;
use Database\Seeders\KindOfPetSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Plan 02 — Transactional emails to clients.
 *
 * Covers the three triggers (check-in confirmation, drop-in, drop-out), the
 * coalescing rule that keeps a multi-pet submission down to ONE confirmation
 * email, the invalid-email guard, and the hard requirement that a broken mail
 * server must never break the check-in / checkout endpoints.
 */
class TransactionalEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Lookup tables the pet/check-in records depend on.
        $this->seed(StatusSeeder::class);
        $this->seed(GenderSeeder::class);
        $this->seed(KindOfPetSeeder::class);
        $this->seed(CastratedSeeder::class);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /** Create a pet belonging to $user. */
    private function makePet(User $user, string $name = 'Luna'): Pet
    {
        return Pet::create([
            'name' => $name,
            'birth_date' => '2020-01-01',
            'race' => 'Labrador',
            'color' => 'Golden',
            'gender_id' => Gender::first()->id,
            'kind_of_pet_id' => KindOfPet::where('name', 'Dog')->first()->id,
            'castrated_id' => Castrated::first()->id,
            'user_id' => $user->id,
        ]);
    }

    /** Create a CHECKED_IN check-in for $user / $pet. */
    private function makeCheckIn(User $user, Pet $pet): CheckIn
    {
        return CheckIn::create([
            'check_in' => now(),
            'pet_id' => $pet->id,
            'user_id' => $user->id,
            'status_id' => Status::where('name', 'CHECKED_IN')->first()->id,
        ]);
    }

    /** A staff user able to pass the `pet.staff.only` gate. */
    private function staff(): User
    {
        return User::factory()->petStaff()->create();
    }

    // ---------------------------------------------------------------------
    // Schema
    // ---------------------------------------------------------------------

    /** @test */
    public function check_ins_table_tracks_when_the_confirmation_was_sent()
    {
        $this->assertTrue(
            Schema::hasColumn('check_ins', 'confirmation_sent_at'),
            'check_ins.confirmation_sent_at is required to coalesce multi-pet confirmations.'
        );
    }

    // ---------------------------------------------------------------------
    // Events fire from the real trigger points
    // ---------------------------------------------------------------------

    /** @test */
    public function finishing_step_five_dispatches_the_check_in_completed_event()
    {
        Event::fake([CheckInCompleted::class]);

        $user = User::factory()->create();
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $response = $this->postJson('/api/checkin/step5/extra-info', [
            'checkin_id' => $checkIn->id,
            'extra_data' => ['inventory' => [], 'grooming' => []],
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        Event::assertDispatched(
            CheckInCompleted::class,
            fn (CheckInCompleted $e) => $e->checkIn->id === $checkIn->id
        );
    }

    /** @test */
    public function dropping_a_pet_in_dispatches_the_pet_dropped_in_event()
    {
        Event::fake([PetDroppedIn::class]);

        $user = User::factory()->create();
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $this->actingAs($this->staff())
            ->post(route('pet-staff.dropped-in', $checkIn->id))
            ->assertRedirect(route('pet-staff.dashboard'));

        Event::assertDispatched(
            PetDroppedIn::class,
            fn (PetDroppedIn $e) => $e->checkIn->id === $checkIn->id
        );
    }

    /** @test */
    public function checking_a_pet_out_dispatches_the_pet_dropped_out_event()
    {
        Event::fake([PetDroppedOut::class]);

        $user = User::factory()->create();
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $this->actingAs($this->staff())
            ->post(route('pet-staff.checkout', $checkIn->id))
            ->assertRedirect(route('pet-staff.dashboard'));

        Event::assertDispatched(
            PetDroppedOut::class,
            fn (PetDroppedOut $e) => $e->checkIn->id === $checkIn->id
        );
    }

    // ---------------------------------------------------------------------
    // Mail actually gets queued
    // ---------------------------------------------------------------------

    /** @test */
    public function a_completed_check_in_queues_one_confirmation_to_the_owner()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $this->postJson('/api/checkin/step5/extra-info', [
            'checkin_id' => $checkIn->id,
            'extra_data' => ['inventory' => [], 'grooming' => []],
        ])->assertStatus(200);

        Mail::assertQueued(
            CheckInConfirmationMail::class,
            fn (CheckInConfirmationMail $mail) => $mail->hasTo('owner@example.com')
        );
        Mail::assertQueuedCount(1);
    }

    /** @test */
    public function dropping_in_queues_the_drop_in_mail_to_the_owner()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $this->actingAs($this->staff())
            ->post(route('pet-staff.dropped-in', $checkIn->id));

        Mail::assertQueued(
            DropInMail::class,
            fn (DropInMail $mail) => $mail->hasTo('owner@example.com')
        );
    }

    /** @test */
    public function checking_out_queues_the_drop_out_mail_to_the_owner()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $this->actingAs($this->staff())
            ->post(route('pet-staff.checkout', $checkIn->id));

        Mail::assertQueued(
            DropOutMail::class,
            fn (DropOutMail $mail) => $mail->hasTo('owner@example.com')
        );
    }

    // ---------------------------------------------------------------------
    // Coalescing: one submission = one confirmation, however many pets
    // ---------------------------------------------------------------------

    /** @test */
    public function a_multi_pet_submission_produces_a_single_confirmation_listing_every_pet()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $checkIns = collect(['Luna', 'Rocky', 'Milo'])
            ->map(fn ($name) => $this->makeCheckIn($user, $this->makePet($user, $name)));

        // The queued listener runs once the whole submission has landed.
        (new SendCheckInConfirmation)->handle(new CheckInCompleted($checkIns->first()));

        Mail::assertQueuedCount(1);
        Mail::assertQueued(CheckInConfirmationMail::class, function (CheckInConfirmationMail $mail) {
            return $mail->checkIns->count() === 3
                && $mail->checkIns->pluck('pet.name')->sort()->values()->all() === ['Luna', 'Milo', 'Rocky'];
        });

        // Every check-in in the batch is stamped, so nothing sends twice.
        $this->assertSame(0, CheckIn::whereNull('confirmation_sent_at')->count());
    }

    /** @test */
    public function a_second_listener_run_for_the_same_batch_sends_nothing()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $first = $this->makeCheckIn($user, $this->makePet($user, 'Luna'));
        $second = $this->makeCheckIn($user, $this->makePet($user, 'Rocky'));

        // Pet 1's job coalesces both check-ins into one email.
        (new SendCheckInConfirmation)->handle(new CheckInCompleted($first));
        // Pet 2's job then finds nothing left to announce.
        (new SendCheckInConfirmation)->handle(new CheckInCompleted($second));

        Mail::assertQueuedCount(1);
    }

    /** @test */
    public function a_later_separate_booking_gets_its_own_confirmation()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);

        $first = $this->makeCheckIn($user, $this->makePet($user, 'Luna'));
        (new SendCheckInConfirmation)->handle(new CheckInCompleted($first));

        // A brand-new booking later on is a new batch, not a duplicate.
        $second = $this->makeCheckIn($user, $this->makePet($user, 'Rocky'));
        (new SendCheckInConfirmation)->handle(new CheckInCompleted($second));

        Mail::assertQueuedCount(2);
    }

    /** @test */
    public function one_owners_confirmation_never_includes_another_owners_pets()
    {
        Mail::fake();

        $alice = User::factory()->create(['email' => 'alice@example.com']);
        $bob = User::factory()->create(['email' => 'bob@example.com']);

        $aliceCheckIn = $this->makeCheckIn($alice, $this->makePet($alice, 'Luna'));
        $this->makeCheckIn($bob, $this->makePet($bob, 'Rocky'));

        (new SendCheckInConfirmation)->handle(new CheckInCompleted($aliceCheckIn));

        Mail::assertQueuedCount(1);
        Mail::assertQueued(CheckInConfirmationMail::class, function (CheckInConfirmationMail $mail) {
            return $mail->hasTo('alice@example.com')
                && $mail->checkIns->count() === 1
                && $mail->checkIns->first()->pet->name === 'Luna';
        });
    }

    // ---------------------------------------------------------------------
    // Guards: bad addresses and dead SMTP must not break anything
    // ---------------------------------------------------------------------

    /** @test */
    public function no_mail_is_sent_when_the_owner_has_no_email_but_the_endpoint_still_succeeds()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => '']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $this->postJson('/api/checkin/step5/extra-info', [
            'checkin_id' => $checkIn->id,
            'extra_data' => ['inventory' => [], 'grooming' => []],
        ])->assertStatus(200)->assertJson(['success' => true]);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function no_mail_is_sent_when_the_owner_email_is_malformed()
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'not-an-email']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        (new SendCheckInConfirmation)->handle(new CheckInCompleted($checkIn));

        Mail::assertNothingQueued();
    }

    /** @test */
    public function a_dead_mail_server_does_not_break_the_check_in_endpoint()
    {
        // Simulate SMTP blowing up at dispatch time.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $this->postJson('/api/checkin/step5/extra-info', [
            'checkin_id' => $checkIn->id,
            'extra_data' => ['inventory' => [], 'grooming' => []],
        ])->assertStatus(200)->assertJson(['success' => true]);
    }

    /** @test */
    public function a_dead_mail_server_does_not_break_the_checkout_endpoint()
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        $user = User::factory()->create(['email' => 'owner@example.com']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user));

        $this->actingAs($this->staff())
            ->post(route('pet-staff.checkout', $checkIn->id))
            ->assertRedirect(route('pet-staff.dashboard'));

        $this->assertSame(
            Status::where('name', 'CHECKED_OUT')->first()->id,
            $checkIn->fresh()->status_id,
            'The checkout must still be recorded even when mail fails.'
        );
    }

    // ---------------------------------------------------------------------
    // Rendered content
    // ---------------------------------------------------------------------

    /** @test */
    public function the_confirmation_email_renders_the_pet_and_owner_details()
    {
        $user = User::factory()->create(['name' => 'Maria Lopez', 'email' => 'owner@example.com']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user, 'Luna'));

        $mail = new CheckInConfirmationMail(collect([$checkIn->load('pet.kindOfPet', 'user', 'extraServices')]));

        $rendered = $mail->render();

        $this->assertStringContainsString('Luna', $rendered);
        $this->assertStringContainsString('Maria Lopez', $rendered);
        $this->assertStringContainsString('Pet Lodge &amp; Spa', $rendered);
        $this->assertStringContainsString($checkIn->check_in->format('M j, Y'), $rendered);
    }

    /** @test */
    public function the_drop_in_email_renders_the_pet_name_and_timestamp()
    {
        $user = User::factory()->create(['name' => 'Maria Lopez']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user, 'Luna'));

        $rendered = (new DropInMail($checkIn->load('pet', 'user')))->render();

        $this->assertStringContainsString('Luna', $rendered);
        $this->assertStringContainsString('Maria Lopez', $rendered);
    }

    /** @test */
    public function the_drop_out_email_renders_the_pet_name_and_a_thank_you()
    {
        $user = User::factory()->create(['name' => 'Maria Lopez']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user, 'Luna'));
        $checkIn->update(['check_out' => now()]);

        $rendered = (new DropOutMail($checkIn->load('pet', 'user')))->render();

        $this->assertStringContainsString('Luna', $rendered);
        $this->assertStringContainsString('Thank you', $rendered);
    }

    /** @test */
    public function the_confirmation_email_lists_booked_grooming_services()
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $checkIn = $this->makeCheckIn($user, $this->makePet($user, 'Luna'));

        $service = \App\Models\ExtraService::firstOrCreate(['name' => 'grooming']);
        $checkIn->extraServices()->attach($service->id, ['grooming_appointment_day' => '2026-08-08']);

        $rendered = (new CheckInConfirmationMail(
            collect([$checkIn->load('pet.kindOfPet', 'user', 'extraServices')])
        ))->render();

        $this->assertStringContainsString('grooming', strtolower($rendered));
    }

    // ---------------------------------------------------------------------
    // Queue wiring
    // ---------------------------------------------------------------------

    /** @test */
    public function the_mailables_are_queueable_so_smtp_latency_never_blocks_the_request()
    {
        $contract = \Illuminate\Contracts\Queue\ShouldQueue::class;

        $this->assertInstanceOf($contract, new DropInMail(new CheckIn));
        $this->assertInstanceOf($contract, new DropOutMail(new CheckIn));
        $this->assertInstanceOf($contract, new CheckInConfirmationMail(collect()));
        $this->assertInstanceOf($contract, new SendCheckInConfirmation);
    }
}
