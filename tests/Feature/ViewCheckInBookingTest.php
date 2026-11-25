<?php

namespace Tests\Feature;

use App\Models\Castrated;
use App\Models\CheckIn;
use App\Models\ExtraService;
use App\Models\Food;
use App\Models\Gender;
use App\Models\Item;
use App\Models\KindOfPet;
use App\Models\MomentOfDay;
use App\Models\Pet;
use App\Models\Signature;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\CastratedSeeder;
use Database\Seeders\GenderSeeder;
use Database\Seeders\KindOfPetSeeder;
use Database\Seeders\MomentOfDaySeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The wizard writes one check-in row PER PET. The client sees one visit with
 * every pet they brought, so /view-check-in, Edit and Delete work on the
 * booking (the owner's open rows) rather than on single rows.
 */
class ViewCheckInBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StatusSeeder::class);
        $this->seed(GenderSeeder::class);
        $this->seed(KindOfPetSeeder::class);
        $this->seed(CastratedSeeder::class);
        $this->seed(MomentOfDaySeeder::class);
    }

    private function petFor(User $user, string $name): Pet
    {
        return Pet::create([
            'name' => $name,
            'birth_date' => '2020-01-01',
            'race' => 'Mixed',
            'color' => 'Brown',
            'gender_id' => Gender::first()->id,
            'kind_of_pet_id' => KindOfPet::first()->id,
            'castrated_id' => Castrated::first()->id,
            'user_id' => $user->id,
        ]);
    }

    private function checkInFor(User $user, string $petName, string $status = 'CHECKED_IN', array $items = ['Blue collar']): CheckIn
    {
        $pet = $this->petFor($user, $petName);

        $checkIn = CheckIn::create([
            'check_in' => now()->subHour(),
            'check_out' => $status === 'CHECKED_OUT' ? now() : null,
            'pet_id' => $pet->id,
            'user_id' => $user->id,
            'status_id' => Status::where('name', $status)->first()->id,
        ]);

        Food::create([
            'name' => "{$petName} kibble",
            'pet_id' => $pet->id,
            'check_in_id' => $checkIn->id,
            'moment_of_day_id' => MomentOfDay::first()->id,
        ]);

        // Step 5 copies the booking's inventory onto every pet's row.
        foreach ($items as $item) {
            Item::create(['name' => $item, 'pet_id' => $pet->id, 'check_in_id' => $checkIn->id]);
        }

        return $checkIn;
    }

    /** @test */
    public function the_view_shows_one_check_in_listing_every_pet_of_the_booking(): void
    {
        $owner = User::factory()->create(['phone' => '0123456789']);
        $first = $this->checkInFor($owner, 'Leouch');
        $this->checkInFor($owner, 'Mauro');
        $this->checkInFor($owner, 'Ghost', 'CANCELLED');

        $response = $this->get(route('view-check-in', ['phone' => '0123456789']));

        $response->assertOk();
        $response->assertSee('Leouch');
        $response->assertSee('Mauro');
        $response->assertDontSee('Ghost');

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'Check-in ID#'), 'one booking card, not one per pet');
        $this->assertStringContainsString('Check-in ID# '.str_pad($first->id, 4, '0', STR_PAD_LEFT), $html);
        $this->assertSame(1, substr_count($html, 'Blue collar'), 'shared items are listed once');
    }

    /** @test */
    public function editing_any_pet_prefills_the_wizard_with_the_whole_booking(): void
    {
        $owner = User::factory()->create();
        $first = $this->checkInFor($owner, 'Leouch');
        $second = $this->checkInFor($owner, 'Mauro');
        $second->extraServices()->attach(ExtraService::firstOrCreate(['name' => 'bath'])->id);

        $this->get(route('edit-check-in', $second->id))->assertRedirect();

        $wizard = session('wizard');
        $this->assertSame($first->id, (int) $wizard['editing_check_in_id']);
        $this->assertSame(['Leouch', 'Mauro'], array_map(fn ($pet) => $pet['info']['petName'], $wizard['prefill']['pets']));
        // Without the index list the submission grooms every pet.
        $this->assertSame([1], $wizard['prefill']['grooming']['pets']);
        $this->assertTrue($wizard['prefill']['grooming']['bath']);
    }

    /** @test */
    public function deleting_the_booking_removes_every_open_row_and_nothing_else(): void
    {
        $owner = User::factory()->create(['phone' => '0123456789']);
        $first = $this->checkInFor($owner, 'Leouch');
        $second = $this->checkInFor($owner, 'Mauro');
        $history = $this->checkInFor($owner, 'Oldie', 'CHECKED_OUT');
        $other = $this->checkInFor(User::factory()->create(), 'Stranger');

        $this->delete(route('delete-booking', $second->id))
            ->assertRedirect(route('view-check-in', ['phone' => '0123456789']));

        $this->assertDatabaseMissing('check_ins', ['id' => $first->id]);
        $this->assertDatabaseMissing('check_ins', ['id' => $second->id]);
        $this->assertDatabaseMissing('foods', ['check_in_id' => $first->id]);
        $this->assertDatabaseMissing('items', ['check_in_id' => $second->id]);
        $this->assertDatabaseHas('check_ins', ['id' => $history->id]);
        $this->assertDatabaseHas('check_ins', ['id' => $other->id]);
    }

    /** @test */
    public function removing_one_pet_keeps_the_rest_of_the_booking(): void
    {
        $owner = User::factory()->create();
        $first = $this->checkInFor($owner, 'Leouch');
        $second = $this->checkInFor($owner, 'Mauro');

        $this->delete(route('delete-check-in', $second->id))->assertRedirect();

        $this->assertDatabaseHas('check_ins', ['id' => $first->id]);
        $this->assertDatabaseMissing('check_ins', ['id' => $second->id]);
        $this->assertDatabaseMissing('items', ['check_in_id' => $second->id]);
    }

    /** @test */
    public function pruning_after_an_edit_deletes_only_pets_removed_in_the_wizard(): void
    {
        $owner = User::factory()->create();
        $first = $this->checkInFor($owner, 'Leouch');
        $removed = $this->checkInFor($owner, 'Mauro');
        $history = $this->checkInFor($owner, 'Oldie', 'CHECKED_OUT');
        $other = $this->checkInFor(User::factory()->create(), 'Stranger');

        $this->postJson(route('check-in.booking.prune'), [
            'check_in_id' => $first->id,
            'pet_ids' => [$first->pet_id],
        ])->assertOk()->assertJson([
            'success' => true,
            'data' => ['deleted_check_in_ids' => [$removed->id], 'kept_signed_check_in_ids' => []],
        ]);

        $this->assertDatabaseHas('check_ins', ['id' => $first->id]);
        $this->assertDatabaseMissing('check_ins', ['id' => $removed->id]);
        $this->assertDatabaseMissing('foods', ['check_in_id' => $removed->id]);
        $this->assertDatabaseHas('check_ins', ['id' => $history->id]);
        $this->assertDatabaseHas('check_ins', ['id' => $other->id]);
    }

    /** @test */
    public function pruning_refuses_an_empty_pet_list(): void
    {
        $owner = User::factory()->create();
        $checkIn = $this->checkInFor($owner, 'Leouch');

        $this->postJson(route('check-in.booking.prune'), [
            'check_in_id' => $checkIn->id,
            'pet_ids' => [],
        ])->assertStatus(422);

        $this->assertDatabaseHas('check_ins', ['id' => $checkIn->id]);
    }

    // ---------------------------------------------------------------------
    // A signed check-in is legal evidence: only pet staff (or above) may delete it
    // ---------------------------------------------------------------------

    private function sign(CheckIn $checkIn): CheckIn
    {
        Signature::create([
            'user_id' => $checkIn->user_id,
            'check_in_id' => $checkIn->id,
            'path' => 'signatures/2026/11/test.png',
            'context' => 'drop-in',
        ]);

        return $checkIn;
    }

    /** @test */
    public function a_client_cannot_delete_a_signed_pet(): void
    {
        $owner = User::factory()->create();
        $signed = $this->sign($this->checkInFor($owner, 'Leouch'));

        $this->deleteJson(route('delete-check-in', $signed->id))->assertForbidden();
        $this->actingAs($owner)->deleteJson(route('delete-check-in', $signed->id))->assertForbidden();

        $this->assertDatabaseHas('check_ins', ['id' => $signed->id]);
        $this->assertDatabaseHas('signatures', ['check_in_id' => $signed->id]);
    }

    /** @test */
    public function a_client_cannot_delete_a_booking_with_a_signed_pet_and_nothing_is_deleted(): void
    {
        $owner = User::factory()->create();
        $unsigned = $this->checkInFor($owner, 'Leouch');
        $signed = $this->sign($this->checkInFor($owner, 'Mauro'));

        $this->deleteJson(route('delete-booking', $unsigned->id))->assertForbidden();

        $this->assertDatabaseHas('check_ins', ['id' => $unsigned->id]);
        $this->assertDatabaseHas('check_ins', ['id' => $signed->id]);
    }

    /** @test */
    public function pet_staff_and_super_admins_can_delete_signed_check_ins(): void
    {
        $owner = User::factory()->create();
        $pet = $this->sign($this->checkInFor($owner, 'Leouch'));
        $booking = $this->sign($this->checkInFor($owner, 'Mauro'));

        $this->actingAs(User::factory()->petStaff()->create())
            ->deleteJson(route('delete-check-in', $pet->id))->assertRedirect();
        $this->assertDatabaseMissing('check_ins', ['id' => $pet->id]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->deleteJson(route('delete-booking', $booking->id))->assertRedirect();
        $this->assertDatabaseMissing('check_ins', ['id' => $booking->id]);
    }

    /** @test */
    public function pruning_keeps_a_signed_pet_unless_staff_is_editing(): void
    {
        $owner = User::factory()->create();
        $kept = $this->checkInFor($owner, 'Leouch');
        $signed = $this->sign($this->checkInFor($owner, 'Mauro'));
        $payload = ['check_in_id' => $kept->id, 'pet_ids' => [$kept->pet_id]];

        $this->postJson(route('check-in.booking.prune'), $payload)->assertOk()->assertJson([
            'data' => ['deleted_check_in_ids' => [], 'kept_signed_check_in_ids' => [$signed->id]],
        ]);
        $this->assertDatabaseHas('check_ins', ['id' => $signed->id]);

        $this->actingAs(User::factory()->petStaff()->create())
            ->postJson(route('check-in.booking.prune'), $payload)->assertOk()->assertJson([
                'data' => ['deleted_check_in_ids' => [$signed->id], 'kept_signed_check_in_ids' => []],
            ]);
        $this->assertDatabaseMissing('check_ins', ['id' => $signed->id]);
    }

    /** @test */
    public function the_view_offers_delete_on_a_signed_pet_only_to_staff(): void
    {
        $owner = User::factory()->create(['phone' => '0123456789']);
        $first = $this->checkInFor($owner, 'Leouch');
        $signed = $this->sign($this->checkInFor($owner, 'Mauro'));
        $url = route('view-check-in', ['phone' => '0123456789']);

        $client = $this->get($url)->assertOk()->getContent();
        $this->assertStringNotContainsString(route('delete-check-in', $signed->id), $client);
        // A booking with a signed pet cannot be deleted as a whole either.
        $this->assertStringNotContainsString(route('delete-booking', $first->id), $client);
        // The unsigned pet can still be removed by the client.
        $this->assertStringContainsString(route('delete-check-in', $first->id), $client);
        $this->assertStringContainsString('Signed — only pet staff can delete', $client);

        $staff = $this->actingAs(User::factory()->petStaff()->create())->get($url)->assertOk()->getContent();
        $this->assertStringContainsString(route('delete-check-in', $signed->id), $staff);
    }
}
