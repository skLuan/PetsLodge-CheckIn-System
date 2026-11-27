<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Food;
use App\Models\Pet;
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
 * Plan 04: health answers and food are per pet. Steps 2–5 run once per pet, so
 * each pet must end up with its own warnings, "Which?" details and food rows —
 * and the details text must survive a round trip back into the edit form.
 */
class MultiPetHealthFoodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StatusSeeder::class);
        $this->seed(GenderSeeder::class);
        $this->seed(KindOfPetSeeder::class);
        $this->seed(CastratedSeeder::class);

        Event::fake();
    }

    private function submitPet(int $userId, array $info, array $health, array $feeding): int
    {
        $petId = $this->postJson('/api/checkin/step2/pet-info', [
            'user_id' => $userId,
            'pet_info' => $info,
        ])->assertOk()->json('data.pet_id');

        $this->postJson('/api/checkin/step3/pet-health', [
            'pet_id' => $petId,
            'health_data' => $health,
            'feeding_data' => $feeding,
            'medication_data' => [],
        ])->assertOk();

        return $petId;
    }

    public function test_two_pets_keep_their_own_health_and_food(): void
    {
        $user = User::factory()->create();

        $catId = $this->submitPet(
            $user->id,
            ['petName' => 'Misha', 'petType' => 'Cat', 'petBreed' => 'Siamese', 'petColor' => 'Cream'],
            ['unusualHealthBehavior' => true, 'healthBehaviors' => 'Vomiting', 'warnings' => 'Food aggressive'],
            [['day_time' => 'morning', 'feeding_med_details' => 'Tuna']],
        );
        $dogId = $this->submitPet(
            $user->id,
            ['petName' => 'Rex', 'petType' => 'Dog', 'petBreed' => 'Beagle', 'petColor' => 'Tricolor'],
            ['unusualHealthBehavior' => false, 'healthBehaviors' => '', 'warnings' => 'Anxious'],
            [['day_time' => 'evening', 'feeding_med_details' => 'Kibble']],
        );

        $cat = Pet::find($catId);
        $dog = Pet::find($dogId);

        $this->assertSame('Vomiting', $cat->health_conditions);
        $this->assertSame('Food aggressive', $cat->warnings);
        $this->assertNull($dog->health_conditions);
        $this->assertSame('Anxious', $dog->warnings);

        $this->assertSame(['Tuna'], Food::where('pet_id', $catId)->pluck('name')->all());
        $this->assertSame(['Kibble'], Food::where('pet_id', $dogId)->pluck('name')->all());
    }

    public function test_health_details_round_trip_into_the_edit_form(): void
    {
        $user = User::factory()->create();

        $petId = $this->submitPet(
            $user->id,
            ['petName' => 'Misha', 'petType' => 'Cat', 'petBreed' => 'Siamese', 'petColor' => 'Cream'],
            ['unusualHealthBehavior' => true, 'healthBehaviors' => 'Seizures', 'warnings' => ''],
            [],
        );

        $checkIn = CheckIn::where('pet_id', $petId)->firstOrFail();
        $health = app(CheckInTransformer::class)
            ->transformCheckInToCookieFormat($checkIn->fresh(CheckIn::SUMMARY_RELATIONS))['pets'][0]['health'];

        $this->assertTrue($health['unusualHealthBehavior']);
        $this->assertSame('Seizures', $health['healthBehaviors']);
    }

    public function test_warnings_alone_do_not_flip_unusual_behavior_to_yes(): void
    {
        $user = User::factory()->create();

        $petId = $this->submitPet(
            $user->id,
            ['petName' => 'Rex', 'petType' => 'Dog', 'petBreed' => 'Beagle', 'petColor' => 'Tricolor'],
            ['unusualHealthBehavior' => false, 'healthBehaviors' => '', 'warnings' => 'Anxious'],
            [],
        );

        $checkIn = CheckIn::where('pet_id', $petId)->firstOrFail();
        $health = app(CheckInTransformer::class)
            ->transformCheckInToCookieFormat($checkIn->fresh(CheckIn::SUMMARY_RELATIONS))['pets'][0]['health'];

        $this->assertFalse($health['unusualHealthBehavior']);
        $this->assertSame('Anxious', $health['warnings']);
    }

    public function test_legacy_boolean_health_conditions_still_load(): void
    {
        $user = User::factory()->create();
        $petId = $this->submitPet(
            $user->id,
            ['petName' => 'Old', 'petType' => 'Dog', 'petBreed' => 'Mutt', 'petColor' => 'Brown'],
            ['unusualHealthBehavior' => false, 'healthBehaviors' => '', 'warnings' => ''],
            [],
        );
        Pet::whereKey($petId)->update(['health_conditions' => '1']);

        $checkIn = CheckIn::where('pet_id', $petId)->firstOrFail();
        $health = app(CheckInTransformer::class)
            ->transformCheckInToCookieFormat($checkIn->fresh(CheckIn::SUMMARY_RELATIONS))['pets'][0]['health'];

        $this->assertTrue($health['unusualHealthBehavior']);
        $this->assertSame('', $health['healthBehaviors']);
    }
}
