<?php

namespace Tests\Feature;

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
use Tests\TestCase;

/**
 * The check-in wizard (Process.blade.php) seeds the browser draft from the
 * session. That data used to live in the generic `checkin_data` key, which
 * nothing ever removed: every reload re-sent it and the front end merged it
 * over the draft again (wiping feeding added since), and the drop-in flow's
 * own `checkin_data` leaked into the wizard for whichever owner came next.
 */
class WizardPrefillSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StatusSeeder::class);
        $this->seed(GenderSeeder::class);
        $this->seed(KindOfPetSeeder::class);
        $this->seed(CastratedSeeder::class);
    }

    private function activeCheckIn(): CheckIn
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

        return CheckIn::create([
            'check_in' => now()->subDay(),
            'pet_id' => $pet->id,
            'user_id' => $user->id,
            'status_id' => Status::where('name', 'CHECKED_IN')->first()->id,
        ]);
    }

    /** @test */
    public function editing_prefills_the_wizard_once_and_a_reload_gets_no_session_data(): void
    {
        $checkIn = $this->activeCheckIn();
        $phone = $checkIn->user->phone;

        $this->get(route('edit-check-in', $checkIn->id))
            ->assertRedirect(route('new-form-pre-filled', ['phone' => $phone]));

        // The page the redirect lands on carries the check-in and the editing flags.
        $first = $this->get(route('new-form-pre-filled', ['phone' => $phone]));
        $first->assertOk();
        $first->assertSee('data-editing-mode="true"', false);
        $first->assertSee('data-editing-check-in-id="'.$checkIn->id.'"', false);
        $this->assertStringContainsString('Luna', $this->sessionCheckinAttribute($first->getContent()));

        // A reload must not hand the same data to the front end again.
        $reload = $this->get(route('new-form-pre-filled', ['phone' => $phone]));
        $reload->assertOk();
        $reload->assertSee('data-editing-mode="false"', false);
        $reload->assertSee('data-editing-check-in-id=""', false);
        $this->assertSame('null', $this->sessionCheckinAttribute($reload->getContent()));
    }

    /** @test */
    public function drop_in_session_data_never_reaches_the_wizard(): void
    {
        // What DropInController leaves behind for the previous owner.
        $response = $this->withSession([
            'checkin_data' => ['user' => ['info' => ['name' => 'Stale Owner']]],
            'editing_mode' => true,
            'editing_check_in_id' => 999,
        ])->get(route('new-form'));

        $response->assertOk();
        $this->assertSame('null', $this->sessionCheckinAttribute($response->getContent()));
        $response->assertSee('data-editing-mode="false"', false);
        $response->assertDontSee('Stale Owner');
    }

    private function sessionCheckinAttribute(string $html): string
    {
        preg_match('/data-session-checkin="([^"]*)"/', $html, $matches);

        // Process.blade.php escapes twice (htmlspecialchars inside {{ }}).
        return html_entity_decode(html_entity_decode($matches[1] ?? '', ENT_QUOTES), ENT_QUOTES);
    }
}
