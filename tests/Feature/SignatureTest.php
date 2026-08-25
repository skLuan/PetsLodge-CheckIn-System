<?php

namespace Tests\Feature;

use App\Models\Castrated;
use App\Models\CheckIn;
use App\Models\Gender;
use App\Models\KindOfPet;
use App\Models\Pet;
use App\Models\Signature;
use App\Models\Status;
use App\Models\TermsAndConditions;
use App\Models\User;
use App\Services\PdfService;
use App\Services\PrintNodeService;
use Database\Seeders\CastratedSeeder;
use Database\Seeders\GenderSeeder;
use Database\Seeders\KindOfPetSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\TermsAndConditionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Plan 03 — Signature module.
 *
 * A signature is per-visit consent, so it is stored on its own append-only table
 * and pinned to the Terms & Conditions version that was active when it was drawn
 * (Plan 01). The PNG itself is personal data: it lives on the PRIVATE `local`
 * disk and is only ever served through an authenticated route.
 */
class SignatureTest extends TestCase
{
    use RefreshDatabase;

    /** A real 1x1 PNG — small, but with genuine PNG magic bytes. */
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StatusSeeder::class);
        $this->seed(GenderSeeder::class);
        $this->seed(KindOfPetSeeder::class);
        $this->seed(CastratedSeeder::class);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function dataUrl(string $base64 = self::PNG_1X1, string $mime = 'image/png'): string
    {
        return "data:{$mime};base64,{$base64}";
    }

    private function staff(): User
    {
        return User::factory()->petStaff()->create();
    }

    private function makeCheckIn(?User $owner = null): CheckIn
    {
        $owner ??= User::factory()->create();

        $pet = Pet::create([
            'name' => 'Luna',
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

    // ---------------------------------------------------------------------
    // Schema
    // ---------------------------------------------------------------------

    /** @test */
    public function signatures_table_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('signatures'));

        foreach (['id', 'user_id', 'check_in_id', 'terms_and_conditions_id', 'path', 'context', 'created_at'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('signatures', $column),
                "signatures table is missing the `{$column}` column"
            );
        }
    }

    // ---------------------------------------------------------------------
    // Storing a signature
    // ---------------------------------------------------------------------

    /** @test */
    public function staff_can_store_a_signature_and_the_png_lands_on_the_private_disk(): void
    {
        Storage::fake('local');
        $this->seed(TermsAndConditionsSeeder::class);

        $checkIn = $this->makeCheckIn();

        $response = $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl(),
                'check_in_id' => $checkIn->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'url']);

        $signature = Signature::firstOrFail();

        $this->assertSame($checkIn->id, $signature->check_in_id);
        $this->assertSame(
            $checkIn->user_id,
            $signature->user_id,
            'The signature must belong to the pet OWNER, not the staff member operating the tablet.'
        );
        $this->assertSame(TermsAndConditions::active()->first()->id, $signature->terms_and_conditions_id);
        $this->assertSame('drop-in', $signature->context);

        Storage::disk('local')->assertExists($signature->path);
        $this->assertStringStartsWith('signatures/', $signature->path);
        $this->assertStringEndsWith('.png', $signature->path);
        $this->assertSame(base64_decode(self::PNG_1X1), Storage::disk('local')->get($signature->path));
    }

    /** @test */
    public function a_signature_is_never_written_to_the_public_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl(),
                'check_in_id' => $checkIn->id,
            ])->assertCreated();

        $this->assertEmpty(
            Storage::disk('public')->allFiles(),
            'Signatures are personal data and must never be publicly reachable.'
        );
    }

    /** @test */
    public function the_stored_path_is_relative_so_urls_are_built_on_read(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl(),
                'check_in_id' => $checkIn->id,
            ])->assertCreated();

        $signature = Signature::firstOrFail();

        $this->assertStringNotContainsString('http', $signature->path);
        $this->assertSame(route('signatures.show', $signature), $signature->url());
    }

    /** @test */
    public function the_context_can_be_set_for_future_signature_points(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl(),
                'check_in_id' => $checkIn->id,
                'context' => 'check-in',
            ])->assertCreated();

        $this->assertSame('check-in', Signature::firstOrFail()->context);
    }

    /** @test */
    public function an_unknown_context_is_rejected(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl(),
                'check_in_id' => $checkIn->id,
                'context' => 'whatever',
            ])->assertStatus(422)->assertJsonValidationErrors('context');
    }

    /** @test */
    public function re_signing_appends_a_new_row_instead_of_overwriting_the_old_one(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();
        $staff = $this->staff();

        $this->actingAs($staff)->postJson(route('signatures.store'), [
            'image' => $this->dataUrl(),
            'check_in_id' => $checkIn->id,
        ])->assertCreated();

        $this->actingAs($staff)->postJson(route('signatures.store'), [
            'image' => $this->dataUrl(),
            'check_in_id' => $checkIn->id,
        ])->assertCreated();

        $this->assertSame(2, Signature::count(), 'Signatures are an audit trail — never overwrite one.');
        $this->assertCount(
            2,
            array_unique(Signature::pluck('path')->all()),
            'Each signature needs its own file.'
        );
    }

    // ---------------------------------------------------------------------
    // Rejected payloads
    // ---------------------------------------------------------------------

    /** @test */
    public function a_payload_that_is_not_base64_is_rejected(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => 'data:image/png;base64,@@@@ not base64 @@@@',
                'check_in_id' => $checkIn->id,
            ])->assertStatus(422)->assertJsonValidationErrors('image');

        $this->assertSame(0, Signature::count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    /** @test */
    public function a_payload_without_a_data_url_prefix_is_rejected(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => self::PNG_1X1,
                'check_in_id' => $checkIn->id,
            ])->assertStatus(422)->assertJsonValidationErrors('image');
    }

    /** @test */
    public function a_non_png_image_is_rejected_even_when_the_data_url_claims_png(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        // Real JPEG magic bytes behind an `image/png` label — the declared mime is
        // attacker-controlled, so the decoded bytes are what must be checked.
        $jpegBytes = base64_encode("\xFF\xD8\xFF\xE0".str_repeat("\x00", 64));

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl($jpegBytes),
                'check_in_id' => $checkIn->id,
            ])->assertStatus(422)->assertJsonValidationErrors('image');

        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    /** @test */
    public function a_data_url_declaring_a_non_png_mime_is_rejected(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl(self::PNG_1X1, 'image/jpeg'),
                'check_in_id' => $checkIn->id,
            ])->assertStatus(422)->assertJsonValidationErrors('image');
    }

    /** @test */
    public function an_oversized_signature_is_rejected(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        // Valid PNG header followed by ~1.5MB of padding — over the 1MB cap.
        $huge = base64_encode(base64_decode(self::PNG_1X1).str_repeat('A', 1500000));

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl($huge),
                'check_in_id' => $checkIn->id,
            ])->assertStatus(422)->assertJsonValidationErrors('image');

        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    /** @test */
    public function a_signature_must_reference_a_real_check_in(): void
    {
        Storage::fake('local');

        $this->actingAs($this->staff())
            ->postJson(route('signatures.store'), [
                'image' => $this->dataUrl(),
                'check_in_id' => 99999,
            ])->assertStatus(422)->assertJsonValidationErrors('check_in_id');
    }

    // ---------------------------------------------------------------------
    // Authorization
    // ---------------------------------------------------------------------

    /** @test */
    public function a_guest_cannot_store_a_signature(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->post(route('signatures.store'), [
            'image' => $this->dataUrl(),
            'check_in_id' => $checkIn->id,
        ])->assertRedirect();

        $this->assertSame(0, Signature::count());
    }

    /** @test */
    public function a_client_user_cannot_store_a_signature(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        // PetStaffOnly redirects to '/' rather than aborting 403 — see AGENTS.md.
        $this->actingAs(User::factory()->create())
            ->post(route('signatures.store'), [
                'image' => $this->dataUrl(),
                'check_in_id' => $checkIn->id,
            ])->assertRedirect('/');

        $this->assertSame(0, Signature::count());
    }

    /** @test */
    public function staff_can_view_a_stored_signature_image(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())->postJson(route('signatures.store'), [
            'image' => $this->dataUrl(),
            'check_in_id' => $checkIn->id,
        ])->assertCreated();

        $signature = Signature::firstOrFail();

        $response = $this->actingAs($this->staff())->get(route('signatures.show', $signature));

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('content-type'));
    }

    /** @test */
    public function a_guest_cannot_view_a_stored_signature_image(): void
    {
        Storage::fake('local');
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())->postJson(route('signatures.store'), [
            'image' => $this->dataUrl(),
            'check_in_id' => $checkIn->id,
        ])->assertCreated();

        $signature = Signature::firstOrFail();

        $this->post('/logout');

        $this->get(route('signatures.show', $signature))->assertRedirect();
    }

    // ---------------------------------------------------------------------
    // The drop-in gate
    // ---------------------------------------------------------------------

    /** @test */
    public function a_drop_in_cannot_be_completed_without_a_signature(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $checkIn = $this->makeCheckIn();

        $this->postJson('/api/readyToPrint', [
            'info' => ['id' => $checkIn->id, 'pets' => []],
        ])->assertStatus(422)->assertJsonPath('requiresSignature', true);

        $this->assertEmpty(
            Storage::disk('public')->allFiles(),
            'The gate must short-circuit before any PDF is generated.'
        );
    }

    /** @test */
    public function a_drop_in_without_a_check_in_id_cannot_be_completed_either(): void
    {
        Storage::fake('public');

        $this->postJson('/api/readyToPrint', [
            'info' => ['pets' => []],
        ])->assertStatus(422)->assertJsonPath('requiresSignature', true);

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    /** @test */
    public function a_signed_drop_in_passes_the_gate_and_reaches_the_printer(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $checkIn = $this->makeCheckIn();

        Signature::create([
            'user_id' => $checkIn->user_id,
            'check_in_id' => $checkIn->id,
            'path' => 'signatures/2026/08/test.png',
            'context' => 'drop-in',
        ]);

        $this->mock(PdfService::class, function ($mock) {
            $mock->shouldReceive('generatePdf')->once()->andReturn('/storage/pdfs/fake.pdf');
        });

        $this->mock(PrintNodeService::class, function ($mock) {
            $mock->shouldReceive('sendPrintJob')->once()->andReturn(['success' => true, 'data' => ['id' => 1]]);
        });

        $this->postJson('/api/readyToPrint', [
            'info' => ['id' => $checkIn->id, 'pets' => []],
        ])->assertOk()->assertJsonStructure(['message', 'pdfUri']);
    }

    /** @test */
    public function the_drop_in_confirmation_page_renders_the_pad_wired_to_the_check_in(): void
    {
        $owner = User::factory()->create(['phone' => '3216549879']);
        $checkIn = $this->makeCheckIn($owner);

        $response = $this->actingAs($this->staff())
            ->get(route('drop-in.confirmation', ['phone' => $owner->phone]));

        $response->assertOk()
            ->assertSee('data-signature-pad', false)
            ->assertSee('data-signature-canvas', false)
            // The pad has to know which check-in it is consenting to.
            ->assertSee('data-check-in-id="'.$checkIn->id.'"', false)
            ->assertSee(route('signatures.store'), false)
            // Print stays locked until something is drawn.
            ->assertSee('Sign to Print Check-in');
    }

    // ---------------------------------------------------------------------
    // Printed summary
    // ---------------------------------------------------------------------

    /** @test */
    public function the_printed_summary_embeds_the_signature_inline(): void
    {
        Storage::fake('local');
        $this->seed(TermsAndConditionsSeeder::class);

        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())->postJson(route('signatures.store'), [
            'image' => $this->dataUrl(),
            'check_in_id' => $checkIn->id,
        ])->assertCreated();

        $html = view('pdf-for-print', [
            'checkinData' => ['id' => $checkIn->id, 'pets' => []],
            'signature' => Signature::firstOrFail(),
        ])->render();

        // dompdf cannot fetch the authenticated route, so the bytes must be inline.
        $this->assertStringContainsString('data:image/png;base64,'.self::PNG_1X1, $html);
        $this->assertStringContainsString('Client Signature', $html);
        $this->assertStringContainsString('Terms &amp; Conditions v1', $html);
        $this->assertStringNotContainsString(route('signatures.show', Signature::firstOrFail()), $html);
    }

    /** @test */
    public function the_printed_summary_renders_without_a_signature(): void
    {
        $html = view('pdf-for-print', [
            'checkinData' => ['id' => 1, 'pets' => []],
            'signature' => null,
        ])->render();

        $this->assertStringNotContainsString('Client Signature', $html);
    }
}
