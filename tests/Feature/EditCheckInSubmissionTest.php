<?php

namespace Tests\Feature;

use App\Models\Castrated;
use App\Models\CheckIn;
use App\Models\Food;
use App\Models\Gender;
use App\Models\KindOfPet;
use App\Models\MomentOfDay;
use App\Models\Pet;
use App\Models\Status;
use App\Models\User;
use App\Services\CheckInTransformer;
use Database\Seeders\CastratedSeeder;
use Database\Seeders\GenderSeeder;
use Database\Seeders\KindOfPetSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Editing an existing check-in re-runs the same step1→step5 sequence as a new
 * one. It used to create a fresh pet AND a fresh check-in row for every pet in
 * the form, so editing a 1-pet booking and adding a second pet left the owner
 * with 3 check-ins instead of 2.
 */
class EditCheckInSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StatusSeeder::class);
        $this->seed(GenderSeeder::class);
        $this->seed(KindOfPetSeeder::class);
        $this->seed(CastratedSeeder::class);

        // The confirmation email is irrelevant here.
        Event::fake();
    }

    /** An owner with one pet already checked in, fed twice a day, carrying a leash. */
    private function existingBooking(): CheckIn
    {
        $user = User::factory()->create();

        $pet = Pet::create([
            'name' => 'Luna',
            'birth_date' => '2020-01-01',
            'race' => 'Labrador',
            'color' => 'Golden',
            'gender_id' => Gender::first()->id,
            'kind_of_pet_id' => KindOfPet::where('name', 'Dog')->first()->id,
            'castrated_id' => Castrated::first()->id,
            'user_id' => $user->id,
        ]);

        $checkIn = CheckIn::create([
            'check_in' => now()->subDay(),
            'pet_id' => $pet->id,
            'user_id' => $user->id,
            'status_id' => Status::where('name', 'CHECKED_IN')->first()->id,
        ]);

        foreach (['morning', 'evening'] as $moment) {
            Food::create([
                'name' => 'Kibble',
                'pet_id' => $pet->id,
                'moment_of_day_id' => MomentOfDay::firstOrCreate(['name' => $moment])->id,
                'check_in_id' => $checkIn->id,
            ]);
        }
        $checkIn->items()->create(['name' => 'Leash', 'pet_id' => $pet->id]);

        return $checkIn->fresh(CheckIn::SUMMARY_RELATIONS);
    }

    /** Replay what SubmissionManager.submitSequentialCheckIn sends for one pet. */
    private function submitPet(int $userId, array $pet, array $inventory, array $grooming = []): int
    {
        $petId = $this->postJson('/api/checkin/step2/pet-info', [
            'user_id' => $userId,
            'pet_info' => $pet['info'],
        ])->assertOk()->json('data.pet_id');

        $this->postJson('/api/checkin/step3/pet-health', [
            'pet_id' => $petId,
            'health_data' => $pet['health'] ?? [],
            'feeding_data' => $pet['feeding'] ?? [],
            'medication_data' => $pet['medication'] ?? [],
        ])->assertOk();

        $checkInId = $this->postJson('/api/checkin/step4/checkin-data', [
            'pet_id' => $petId,
            'checkin_data' => ['inventory' => $inventory, 'grooming' => $grooming],
        ])->assertOk()->json('data.checkin_id');

        $this->postJson('/api/checkin/step5/extra-info', [
            'checkin_id' => $checkInId,
            'extra_data' => ['inventory' => $inventory, 'grooming' => $grooming],
        ])->assertOk();

        return $checkInId;
    }

    public function test_editing_cookie_carries_the_existing_pet_id(): void
    {
        $checkIn = $this->existingBooking();

        $cookie = app(CheckInTransformer::class)->transformCheckInToCookieFormat($checkIn);

        $this->assertSame($checkIn->pet_id, $cookie['pets'][0]['info']['petId']);
    }

    public function test_editing_a_booking_and_adding_a_pet_yields_two_check_ins_not_three(): void
    {
        $original = $this->existingBooking();
        $cookie = app(CheckInTransformer::class)->transformCheckInToCookieFormat($original);

        $newPet = [
            'info' => [
                'petName' => 'Toby',
                'petType' => 'Dog',
                'petBreed' => 'Beagle',
                'petColor' => 'Tricolor',
            ],
            'health' => ['unusualHealthBehavior' => false, 'healthBehaviors' => '', 'warnings' => ''],
        ];

        $editedId = $this->submitPet($original->user_id, $cookie['pets'][0], $cookie['inventory']);
        $addedId = $this->submitPet($original->user_id, $newPet, $cookie['inventory']);

        // The edited pet kept its row; only the added pet got a new one.
        $this->assertSame($original->id, $editedId);
        $this->assertNotSame($editedId, $addedId);
        $this->assertSame(2, CheckIn::where('user_id', $original->user_id)->count());
        $this->assertSame(2, Pet::where('user_id', $original->user_id)->count());

        // Re-saving replaced the lists instead of appending to them.
        $this->assertSame(2, $original->foods()->count());
        $this->assertSame(['Leash'], $original->items()->pluck('name')->all());
    }

    public function test_saving_an_edit_twice_does_not_duplicate_anything(): void
    {
        $original = $this->existingBooking();
        $cookie = app(CheckInTransformer::class)->transformCheckInToCookieFormat($original);

        $this->submitPet($original->user_id, $cookie['pets'][0], $cookie['inventory'], ['bath' => true]);
        $this->submitPet($original->user_id, $cookie['pets'][0], $cookie['inventory'], []);

        $this->assertSame(1, CheckIn::where('user_id', $original->user_id)->count());
        $this->assertSame(1, Pet::where('user_id', $original->user_id)->count());
        $this->assertSame(1, $original->items()->count());
        // Unticking a service while editing removes it.
        $this->assertSame(0, $original->extraServices()->count());
    }

    public function test_a_cancelled_stay_is_not_reused_by_a_new_check_in(): void
    {
        $original = $this->existingBooking();
        $original->update(['status_id' => Status::where('name', 'CANCELLED')->first()->id]);
        $cookie = app(CheckInTransformer::class)->transformCheckInToCookieFormat($original);

        $newId = $this->submitPet($original->user_id, $cookie['pets'][0], []);

        $this->assertNotSame($original->id, $newId);
        $this->assertSame(2, $original->foods()->count(), 'the cancelled row keeps its history');
    }
}
