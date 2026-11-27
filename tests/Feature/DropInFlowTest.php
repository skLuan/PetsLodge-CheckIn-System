<?php

namespace Tests\Feature;

use App\Models\Castrated;
use App\Models\CheckIn;
use App\Models\Gender;
use App\Models\KindOfPet;
use App\Models\Pet;
use App\Models\Signature;
use App\Models\Status;
use App\Models\User;
use App\Services\PdfService;
use App\Services\PrintNodeService;
use Database\Seeders\CastratedSeeder;
use Database\Seeders\GenderSeeder;
use Database\Seeders\KindOfPetSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\TermsAndConditionsSeeder;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The drop-in flow end to end, as staff actually walk it:
 *
 *   phone → confirmation page → client signs → "Print"
 *     → signature stored → PDF rendered (template + signature) → sent to printer
 *
 * Written after a real drop-in produced no signature row and no PDF: the
 * confirmation page had rendered with an EMPTY check-in id, so the signature
 * POST could never satisfy `check_in_id required` and the whole flow dead-ended
 * silently.
 */
class DropInFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private array $transactions = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StatusSeeder::class);
        $this->seed(GenderSeeder::class);
        $this->seed(KindOfPetSeeder::class);
        $this->seed(CastratedSeeder::class);
        $this->seed(TermsAndConditionsSeeder::class);

        config(['services.printnode.fake' => false]);
        Storage::fake('local');
        Storage::fake('public');

        $mock = new MockHandler(array_fill(0, 5, new Response(201, [], '623')));
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->transactions));
        $this->app->instance(PrintNodeService::class, new PrintNodeService(new Client(['handler' => $stack])));
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function staff(): User
    {
        return User::factory()->petStaff()->create();
    }

    private function owner(string $phone = '3158008203'): User
    {
        return User::factory()->create(['phone' => $phone, 'name' => 'Varo Prueba']);
    }

    private function checkInFor(User $owner, string $petName): CheckIn
    {
        $pet = Pet::create([
            'name' => $petName,
            'birth_date' => '2020-01-01',
            'race' => 'Labrador',
            'color' => 'Golden',
            'gender_id' => Gender::first()->id,
            'kind_of_pet_id' => KindOfPet::where('name', 'Dog')->first()->id,
            'castrated_id' => Castrated::first()->id,
            'user_id' => $owner->id,
        ]);

        return CheckIn::create([
            'check_in' => now(),
            'pet_id' => $pet->id,
            'user_id' => $owner->id,
            'status_id' => Status::where('name', 'CHECKED_IN')->first()->id,
        ]);
    }

    private function confirmationPage(User $staff, User $owner)
    {
        return $this->actingAs($staff)->get(route('drop-in.confirmation', ['phone' => $owner->phone]));
    }

    private function renderedCheckInId(string $html): ?string
    {
        preg_match('/data-check-in-id="([^"]*)"/', $html, $m);

        return $m[1] ?? null;
    }

    private function sentPayload(int $index = 0): array
    {
        $this->assertArrayHasKey($index, $this->transactions, 'Nothing reached the printer.');

        return json_decode((string) $this->transactions[$index]['request']->getBody(), true);
    }

    // =====================================================================
    // The happy path, step by step
    // =====================================================================

    /** @test */
    public function the_confirmation_page_carries_the_active_check_in_id(): void
    {
        $owner = $this->owner();
        $checkIn = $this->checkInFor($owner, 'Pulsiani');

        $response = $this->confirmationPage($this->staff(), $owner);

        $response->assertOk();

        // Without this the pad cannot post a valid `check_in_id` and the client
        // can sign all day without anything being stored.
        $this->assertSame(
            (string) $checkIn->id,
            $this->renderedCheckInId($response->getContent()),
            'The signature pad must know which check-in it is consenting to.'
        );
    }

    /** @test */
    public function the_full_flow_signs_then_prints(): void
    {
        $staff = $this->staff();
        $owner = $this->owner();
        $checkIn = $this->checkInFor($owner, 'Pulsiani');

        // 1. Staff opens the confirmation page.
        $page = $this->confirmationPage($staff, $owner);
        $checkInId = $this->renderedCheckInId($page->getContent());

        // 2. The client signs — exactly what the pad posts.
        $this->actingAs($staff)->postJson(route('signatures.store'), [
            'image' => 'data:image/png;base64,'.self::PNG_1X1,
            'check_in_id' => $checkInId,
            'context' => 'drop-in',
        ])->assertCreated();

        $signature = Signature::firstOrFail();
        $this->assertSame($checkIn->id, $signature->check_in_id);
        $this->assertSame($owner->id, $signature->user_id, 'The OWNER signs, not the staff member.');

        // 3. "Print" — generate the document, then send it.
        $this->postJson('/api/readyToPrint', [
            'info' => ['id' => $checkInId],
        ])->assertOk();

        // The PDF was stored against the check-in...
        $path = $checkIn->fresh()->document_path;
        $this->assertNotNull($path, 'The generated document must be recorded on the check-in.');
        Storage::disk(PdfService::DISK)->assertExists($path);

        // ...it carries the signature (an image XObject dompdf embedded)...
        $stored = Storage::disk(PdfService::DISK)->get($path);
        $this->assertStringContainsString('/Subtype /Image', $stored);

        // ...and exactly those bytes went to the printer.
        $this->assertSame($stored, base64_decode($this->sentPayload()['content'], true));
    }

    /** @test */
    public function the_printed_document_uses_the_real_check_in_data(): void
    {
        $staff = $this->staff();
        $owner = $this->owner();
        $checkIn = $this->checkInFor($owner, 'Pulsiani');

        $this->actingAs($staff)->postJson(route('signatures.store'), [
            'image' => 'data:image/png;base64,'.self::PNG_1X1,
            'check_in_id' => $checkIn->id,
        ])->assertCreated();

        $captured = null;
        $this->mock(PdfService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('generatePdf')->once()->andReturnUsing(function ($data) use (&$captured) {
                $captured = $data;

                return 'pdfs/2026/08/x.pdf';
            });
            $mock->shouldReceive('pdfBytes')->andReturn("%PDF-1.4\nx");
        });

        $this->postJson('/api/readyToPrint', ['info' => ['id' => $checkIn->id]])->assertOk();

        $this->assertSame('Pulsiani', $captured['pets'][0]['info']['petName'] ?? null);
        $this->assertSame('Varo Prueba', $captured['user']['info']['name'] ?? null);
    }

    // =====================================================================
    // Multi-pet bookings — one signature, one document, every pet
    // =====================================================================

    /** @test */
    public function a_multi_pet_booking_shows_every_pet_on_one_page(): void
    {
        $owner = $this->owner();
        $first = $this->checkInFor($owner, 'Pulsiani');
        $this->checkInFor($owner, 'Mauro');

        $response = $this->confirmationPage($this->staff(), $owner);

        $response->assertOk()->assertSee('Pulsiani')->assertSee('Mauro');

        // One pad, anchored to the first row of the booking.
        $this->assertSame((string) $first->id, $this->renderedCheckInId($response->getContent()));
        $this->assertSame(1, substr_count($response->getContent(), '<canvas'), 'The client signs once for the booking.');
    }

    /** @test */
    public function signing_once_covers_every_check_in_in_the_booking(): void
    {
        $owner = $this->owner();
        $first = $this->checkInFor($owner, 'Pulsiani');
        $second = $this->checkInFor($owner, 'Mauro');

        $this->actingAs($this->staff())->postJson(route('signatures.store'), [
            'image' => 'data:image/png;base64,'.self::PNG_1X1,
            'check_in_id' => $first->id,
        ])->assertCreated()->assertJsonPath('checkInIds', [$first->id, $second->id]);

        $this->assertSame(1, Signature::where('check_in_id', $first->id)->count());
        $this->assertSame(1, Signature::where('check_in_id', $second->id)->count(), 'No pet may be left unsigned.');

        // One signing event, so one image file referenced by both rows.
        $this->assertCount(1, array_unique(Signature::pluck('path')->all()));
    }

    /** @test */
    public function one_document_lists_every_pet_and_is_recorded_on_every_row(): void
    {
        $owner = $this->owner();
        $first = $this->checkInFor($owner, 'Pulsiani');
        $second = $this->checkInFor($owner, 'Mauro');

        $this->actingAs($this->staff())->postJson(route('signatures.store'), [
            'image' => 'data:image/png;base64,'.self::PNG_1X1,
            'check_in_id' => $first->id,
        ])->assertCreated();

        $captured = null;
        $this->mock(PdfService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('generatePdf')->once()->andReturnUsing(function ($data) use (&$captured) {
                $captured = $data;

                return 'pdfs/2026/08/booking.pdf';
            });
            $mock->shouldReceive('pdfBytes')->andReturn("%PDF-1.4\nbooking");
        });

        $this->postJson('/api/readyToPrint', ['info' => ['id' => $first->id]])->assertOk();

        // ONE document...
        $names = collect($captured['pets'])->pluck('info.petName')->all();
        $this->assertSame(['Pulsiani', 'Mauro'], $names, 'The summary must name every pet in the booking.');

        // ...recorded against BOTH rows, so either dashboard entry can re-print it.
        $this->assertSame('pdfs/2026/08/booking.pdf', $first->fresh()->document_path);
        $this->assertSame('pdfs/2026/08/booking.pdf', $second->fresh()->document_path);
    }

    /** @test */
    public function a_collected_pet_is_not_dragged_into_a_later_booking(): void
    {
        $owner = $this->owner();
        $collected = $this->checkInFor($owner, 'Ghost');
        $collected->update(['check_out' => now()]);

        $current = $this->checkInFor($owner, 'Pulsiani');

        $this->actingAs($this->staff())->postJson(route('signatures.store'), [
            'image' => 'data:image/png;base64,'.self::PNG_1X1,
            'check_in_id' => $current->id,
        ])->assertCreated()->assertJsonPath('checkInIds', [$current->id]);

        $this->assertSame(0, Signature::where('check_in_id', $collected->id)->count());
    }

    // =====================================================================
    // Cancelled check-ins are not part of a drop-in
    // (cancel sets the status but leaves check_out null)
    // =====================================================================

    private function cancel(CheckIn $checkIn): CheckIn
    {
        $checkIn->update(['status_id' => Status::where('name', 'CANCELLED')->first()->id]);

        return $checkIn;
    }

    /** @test */
    public function a_cancelled_pet_is_not_shown_on_the_drop_in_page(): void
    {
        $owner = $this->owner();
        $current = $this->checkInFor($owner, 'Pulsiani');
        // Newest row — it would have been picked as the booking's anchor.
        $this->cancel($this->checkInFor($owner, 'Ghost'));

        $response = $this->confirmationPage($this->staff(), $owner);

        $response->assertOk()->assertSee('Pulsiani')->assertDontSee('Ghost');
        $this->assertSame((string) $current->id, $this->renderedCheckInId($response->getContent()));
    }

    /** @test */
    public function an_owner_whose_only_check_in_was_cancelled_is_not_offered_a_signature_pad(): void
    {
        $owner = $this->owner();
        $this->cancel($this->checkInFor($owner, 'Ghost'));

        $response = $this->confirmationPage($this->staff(), $owner);

        $response->assertOk()->assertDontSee('Ghost');
        $this->assertSame(0, substr_count($response->getContent(), '<canvas'));

        $this->actingAs($this->staff())->postJson(route('drop-in.check-user'), ['phone' => $owner->phone])
            ->assertOk()->assertJson(['hasCheckIn' => false]);
    }

    /** @test */
    public function signing_does_not_cover_a_cancelled_pet(): void
    {
        $owner = $this->owner();
        $current = $this->checkInFor($owner, 'Pulsiani');
        $cancelled = $this->cancel($this->checkInFor($owner, 'Ghost'));

        $this->actingAs($this->staff())->postJson(route('signatures.store'), [
            'image' => 'data:image/png;base64,'.self::PNG_1X1,
            'check_in_id' => $current->id,
        ])->assertCreated()->assertJsonPath('checkInIds', [$current->id]);

        $this->assertSame(0, Signature::where('check_in_id', $cancelled->id)->count());
    }

    // =====================================================================
    // The failure that actually happened
    // =====================================================================

    /** @test */
    public function a_user_with_no_active_check_in_is_not_offered_a_signature_pad(): void
    {
        // Every check-in already checked out — this is the exact state that
        // produced a page with data-check-in-id="" and a dead Print button.
        $owner = $this->owner();
        $this->checkInFor($owner, 'Pulsiani')->update(['check_out' => now()]);

        $response = $this->confirmationPage($this->staff(), $owner);

        $response->assertOk();
        $content = $response->getContent();

        // Assert on the ELEMENTS, not on any occurrence of the string — the page
        // script mentions `[data-signature-pad]` in a querySelector either way.
        $this->assertStringNotContainsString('<canvas', $content, 'A pad that cannot save anything must not be shown.');
        $this->assertStringNotContainsString('<button id="print-button"', $content, 'Nothing can be printed without a check-in.');
        $this->assertStringContainsString('No active check-in', $content, 'Staff need to be told why, not left guessing.');
    }

    /** @test */
    public function an_unknown_phone_number_does_not_offer_a_signature_pad(): void
    {
        $response = $this->actingAs($this->staff())
            ->get(route('drop-in.confirmation', ['phone' => '9999999999']));

        $response->assertOk();
        $this->assertStringNotContainsString('<canvas', $response->getContent());
        $this->assertStringNotContainsString('<button id="print-button"', $response->getContent());
    }

    /** @test */
    public function stale_session_data_never_overrides_the_real_active_check_in(): void
    {
        $owner = $this->owner();
        $checkIn = $this->checkInFor($owner, 'Pulsiani');

        // Left over from an earlier wizard run: right shape, wrong (stale) id.
        $response = $this->actingAs($this->staff())
            ->withSession(['checkin_data' => ['id' => 999999, 'user' => ['info' => ['name' => 'Stale']]]])
            ->get(route('drop-in.confirmation', ['phone' => $owner->phone]));

        $response->assertOk();
        $this->assertSame(
            (string) $checkIn->id,
            $this->renderedCheckInId($response->getContent()),
            'The database is the source of truth for which check-in is being dropped in.'
        );
    }

    /** @test */
    public function a_signature_cannot_be_stored_without_a_check_in(): void
    {
        // What the pad would have posted on the broken page.
        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => 'data:image/png;base64,'.self::PNG_1X1,
                'check_in_id' => '',
            ])->assertStatus(422)->assertJsonValidationErrors('check_in_id');

        $this->assertSame(0, Signature::count());
    }
}
