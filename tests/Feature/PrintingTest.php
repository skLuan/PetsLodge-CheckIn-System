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
use App\Services\FakePrintNodeService;
use App\Services\PdfService;
use App\Services\PrintNodeService;
use Database\Seeders\CastratedSeeder;
use Database\Seeders\GenderSeeder;
use Database\Seeders\KindOfPetSeeder;
use Database\Seeders\StatusSeeder;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Plan 07 — printing by upload (`pdf_base64`) instead of by public URL.
 *
 * The old `pdf_uri` flow made PrintNode's cloud servers fetch the PDF, which
 * forced the printed summary — owner PII, health notes and the client's
 * signature — onto a publicly readable disk. These tests pin the new contract:
 * bytes go up in the request body, and nothing is ever public.
 */
class PrintingTest extends TestCase
{
    use RefreshDatabase;

    /** Captured Guzzle transactions for the printer under test. */
    private array $transactions = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StatusSeeder::class);
        $this->seed(GenderSeeder::class);
        $this->seed(KindOfPetSeeder::class);
        $this->seed(CastratedSeeder::class);

        // Never let a test reach the real API or the real disks.
        config(['services.printnode.fake' => false]);
        Storage::fake('local');
        Storage::fake('public');
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * A real PrintNodeService whose HTTP layer is a mock, recording every request.
     *
     * PrintNode answers a successful POST /printjobs with HTTP 201 and the bare
     * job id as the body — not a JSON object.
     */
    private function recordingPrinter(?Response $response = null): PrintNodeService
    {
        $mock = new MockHandler([$response ?? new Response(201, [], '623')]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->transactions));

        return new PrintNodeService(new Client(['handler' => $stack]));
    }

    /** Install the recording printer so controllers resolve it. */
    private function useRecordingPrinter(?Response $response = null): PrintNodeService
    {
        $printer = $this->recordingPrinter($response);
        $this->app->instance(PrintNodeService::class, $printer);

        return $printer;
    }

    /** The JSON body PrintNode was sent. */
    private function sentPayload(int $index = 0): array
    {
        $this->assertArrayHasKey($index, $this->transactions, 'No request reached PrintNode.');

        return json_decode((string) $this->transactions[$index]['request']->getBody(), true);
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

    /** A check-in that has been signed, so it passes the Plan 03 drop-in gate. */
    private function makeSignedCheckIn(): CheckIn
    {
        $checkIn = $this->makeCheckIn();

        Signature::create([
            'user_id' => $checkIn->user_id,
            'check_in_id' => $checkIn->id,
            'path' => 'signatures/2026/08/test.png',
            'context' => 'drop-in',
        ]);

        return $checkIn;
    }

    /** Put a real PDF on the private disk and point the check-in at it. */
    private function givenStoredPdf(CheckIn $checkIn, string $body = 'stored'): string
    {
        $path = 'pdfs/2026/08/'.$body.'.pdf';
        Storage::disk(PdfService::DISK)->put($path, "%PDF-1.4\n".$body);
        $checkIn->update(['document_path' => $path]);

        return $path;
    }

    // =====================================================================
    // Payload shape — the core of this plan
    // =====================================================================

    /** @test */
    public function a_print_job_is_sent_as_pdf_base64(): void
    {
        $this->recordingPrinter()->sendPrintJob("%PDF-1.4\nhello");

        $this->assertSame('pdf_base64', $this->sentPayload()['contentType']);
    }

    /** @test */
    public function the_content_is_base64_that_decodes_to_a_pdf(): void
    {
        $this->recordingPrinter()->sendPrintJob("%PDF-1.4\nhello");

        $content = $this->sentPayload()['content'];
        $decoded = base64_decode($content, true);

        $this->assertNotFalse($decoded, 'content must be valid base64');
        $this->assertStringStartsWith('%PDF-', $decoded);
    }

    /** @test */
    public function the_content_carries_no_data_uri_prefix(): void
    {
        // PrintNode documents raw base64 for pdf_base64 — a `data:` prefix (the
        // shape signature_pad produces, see Plan 03) would corrupt the document.
        $this->recordingPrinter()->sendPrintJob("%PDF-1.4\nhello");

        $this->assertStringNotContainsString('data:', $this->sentPayload()['content']);
        $this->assertStringStartsWith('JVBERi', $this->sentPayload()['content']);
    }

    /** @test */
    public function no_url_is_ever_handed_to_printnode(): void
    {
        $this->recordingPrinter()->sendPrintJob("%PDF-1.4\nhello");

        $payload = $this->sentPayload();

        $this->assertNotSame('pdf_uri', $payload['contentType']);
        $this->assertStringNotContainsString('http', $payload['content']);
    }

    /** @test */
    public function a_bare_integer_response_body_is_handled(): void
    {
        // PrintNode returns `623`, not `{"id":623}`. Anything treating `data` as
        // an array would break here.
        $result = $this->recordingPrinter(new Response(201, [], '623'))
            ->sendPrintJob("%PDF-1.4\nhello");

        $this->assertTrue($result['success']);
        $this->assertSame(623, $result['data']);
    }

    /** @test */
    public function an_api_error_is_returned_not_thrown(): void
    {
        $result = $this->recordingPrinter(new Response(401, [], 'Unauthorized'))
            ->sendPrintJob("%PDF-1.4\nhello");

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }

    /** @test */
    public function an_oversize_document_is_refused_without_an_http_call(): void
    {
        // PrintNode caps the request body at 50MB; base64 inflates by ~33%, so
        // the document ceiling is just under 37.5MB.
        $huge = '%PDF-1.4'.str_repeat('A', PrintNodeService::MAX_DOCUMENT_BYTES + 1);

        $result = $this->recordingPrinter()->sendPrintJob($huge);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('too large', strtolower($result['message']));
        $this->assertCount(0, $this->transactions, 'Nothing should be uploaded once the size check fails.');
    }

    /** @test */
    public function the_job_title_and_printer_are_sent(): void
    {
        config(['services.printnode.printer_id' => '4242']);

        $this->recordingPrinter()->sendPrintJob("%PDF-1.4\nx", ['title' => 'Drop-in: Luna']);

        $payload = $this->sentPayload();

        $this->assertSame(4242, $payload['printerId']);
        $this->assertSame('Drop-in: Luna', $payload['title']);
    }

    // =====================================================================
    // Flow 1 — drop-in
    // =====================================================================

    /** @test */
    public function a_signed_drop_in_uploads_the_generated_pdf(): void
    {
        $this->useRecordingPrinter();
        $checkIn = $this->makeSignedCheckIn();

        $response = $this->postJson('/api/readyToPrint', [
            'info' => ['id' => $checkIn->id, 'pets' => []],
        ]);

        $response->assertOk()->assertJsonStructure(['message', 'documentUrl']);

        $path = $checkIn->fresh()->document_path;
        $this->assertNotNull($path, 'The check-in must remember its document.');

        // What went up the wire is exactly what is on disk.
        $sent = base64_decode($this->sentPayload()['content'], true);
        $this->assertSame(Storage::disk(PdfService::DISK)->get($path), $sent);
    }

    /** @test */
    public function the_signature_gate_still_blocks_an_unsigned_drop_in(): void
    {
        // Regression guard: this plan rewrites the same method the Plan 03 gate
        // lives in.
        $this->useRecordingPrinter();
        $checkIn = $this->makeCheckIn();

        $this->postJson('/api/readyToPrint', [
            'info' => ['id' => $checkIn->id, 'pets' => []],
        ])->assertStatus(422)->assertJsonPath('requiresSignature', true);

        $this->assertNull($checkIn->fresh()->document_path, 'No PDF should be generated for an unsigned drop-in.');
        $this->assertCount(0, $this->transactions);
    }

    /** @test */
    public function a_second_print_reuses_the_stored_pdf(): void
    {
        $this->useRecordingPrinter(new Response(201, [], '623'));
        $checkIn = $this->makeSignedCheckIn();

        $this->postJson('/api/readyToPrint', ['info' => ['id' => $checkIn->id, 'pets' => []]])->assertOk();
        $firstPath = $checkIn->fresh()->document_path;

        // A fresh printer for the second call (the mock queue holds one response).
        $this->useRecordingPrinter(new Response(201, [], '624'));
        $this->transactions = [];

        $this->postJson('/api/readyToPrint', ['info' => ['id' => $checkIn->id, 'pets' => []]])->assertOk();

        $this->assertSame($firstPath, $checkIn->fresh()->document_path, 'The document must not be regenerated.');
        $this->assertCount(1, Storage::disk(PdfService::DISK)->allFiles(), 'A second PDF file means it was regenerated.');
    }

    /** @test */
    public function the_printed_pdf_still_contains_the_signature(): void
    {
        $this->useRecordingPrinter();

        $checkIn = $this->makeCheckIn();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk(Signature::DISK)->put('signatures/2026/08/sig.png', $png);
        Signature::create([
            'user_id' => $checkIn->user_id,
            'check_in_id' => $checkIn->id,
            'path' => 'signatures/2026/08/sig.png',
            'context' => 'drop-in',
        ]);

        $this->postJson('/api/readyToPrint', ['info' => ['id' => $checkIn->id, 'pets' => []]])->assertOk();

        $pdf = base64_decode($this->sentPayload()['content'], true);

        // dompdf writes the inlined signature as an image XObject.
        $this->assertStringContainsString('/Subtype /Image', $pdf);
    }

    /** @test */
    public function the_pdf_is_built_from_server_data_not_the_browser_payload(): void
    {
        // The browser posts whatever `info` it has. Trusting it means a thin or
        // stale payload silently produces a near-empty legal document with a
        // real signature on it. The server already holds the truth.
        $this->useRecordingPrinter();
        $checkIn = $this->makeSignedCheckIn();

        // Capture what the controller actually hands the renderer. dompdf
        // compresses its text streams, so the finished PDF cannot be searched
        // for the pet's name — the input is the honest place to assert.
        $captured = null;
        $this->mock(PdfService::class, function ($mock) use (&$captured) {
            $mock->shouldReceive('generatePdf')->once()->andReturnUsing(function ($data) use (&$captured) {
                $captured = $data;

                return 'pdfs/2026/08/captured.pdf';
            });
            $mock->shouldReceive('pdfBytes')->andReturn("%PDF-1.4\ncaptured");
        });

        $this->postJson('/api/readyToPrint', [
            'info' => ['id' => $checkIn->id],   // deliberately threadbare
        ])->assertOk();

        $this->assertNotNull($captured);
        $this->assertSame('Luna', $captured['pets'][0]['info']['petName'] ?? null, 'The real pet name must come from the database.');
        $this->assertArrayHasKey('user', $captured, 'Owner details must be present even though the payload omitted them.');
        $this->assertNotSame(['id' => $checkIn->id], $captured, 'The posted payload must not be used verbatim.');
    }

    /** @test */
    public function the_printed_summary_contains_no_unrenderable_emoji(): void
    {
        // dompdf's default font has no emoji glyphs — every one printed as "?".
        $html = view('pdf-for-print', [
            'checkinData' => ['id' => 1, 'pets' => []],
            'signature' => null,
        ])->render();

        $this->assertSame(
            0,
            preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]/u', $html),
            'Emoji render as "?" in the PDF — keep them out of pdf-for-print.blade.php.'
        );
    }

    /** @test */
    public function a_printer_failure_is_reported_without_crashing(): void
    {
        // The failure branch is easy to leave behind when the success path is
        // rewritten — it referenced a variable that no longer existed.
        $this->useRecordingPrinter(new Response(401, [], 'Unauthorized'));
        $checkIn = $this->makeSignedCheckIn();

        $response = $this->postJson('/api/readyToPrint', [
            'info' => ['id' => $checkIn->id, 'pets' => []],
        ]);

        $response->assertStatus(500)->assertJsonStructure(['error']);
        $this->assertStringNotContainsString('Undefined variable', $response->json('error') ?? '');

        // The document was still generated and remembered, so a retry reuses it.
        $this->assertNotNull($checkIn->fresh()->document_path);
    }

    /** @test */
    public function a_missing_pdf_file_is_a_clean_error_not_a_500(): void
    {
        $this->useRecordingPrinter();
        $checkIn = $this->makeSignedCheckIn();
        $checkIn->update(['document_path' => 'pdfs/2026/08/vanished.pdf']);

        $response = $this->postJson('/api/readyToPrint', [
            'info' => ['id' => $checkIn->id, 'pets' => []],
        ]);

        $this->assertNotSame(500, $response->status());
        $response->assertStatus(422);
        $this->assertCount(0, $this->transactions);
    }

    // =====================================================================
    // Flow 2 — re-print
    // =====================================================================

    /** @test */
    public function staff_can_reprint_a_stored_document(): void
    {
        $this->useRecordingPrinter();
        $checkIn = $this->makeCheckIn();
        $this->givenStoredPdf($checkIn, 'reprint');

        $this->actingAs($this->staff())
            ->post(route('pet-staff.reprint', $checkIn->id))
            ->assertRedirect(route('pet-staff.dashboard'))
            ->assertSessionHas('success');

        $sent = base64_decode($this->sentPayload()['content'], true);
        $this->assertStringContainsString('reprint', $sent);
    }

    /** @test */
    public function a_check_in_without_a_document_cannot_be_reprinted(): void
    {
        $this->useRecordingPrinter();
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->post(route('pet-staff.reprint', $checkIn->id))
            ->assertRedirect(route('pet-staff.dashboard'))
            ->assertSessionHas('error');

        $this->assertCount(0, $this->transactions);
    }

    /** @test */
    public function reprinting_a_vanished_file_is_an_error_not_a_500(): void
    {
        $this->useRecordingPrinter();
        $checkIn = $this->makeCheckIn();
        $checkIn->update(['document_path' => 'pdfs/2026/08/vanished.pdf']);

        $this->actingAs($this->staff())
            ->post(route('pet-staff.reprint', $checkIn->id))
            ->assertRedirect(route('pet-staff.dashboard'))
            ->assertSessionHas('error');

        $this->assertCount(0, $this->transactions);
    }

    /** @test */
    public function a_guest_cannot_reprint(): void
    {
        $checkIn = $this->makeCheckIn();
        $this->givenStoredPdf($checkIn);

        $this->post(route('pet-staff.reprint', $checkIn->id))->assertRedirect();
    }

    /** @test */
    public function a_client_user_cannot_reprint(): void
    {
        $checkIn = $this->makeCheckIn();
        $this->givenStoredPdf($checkIn);

        $this->actingAs(User::factory()->create())
            ->post(route('pet-staff.reprint', $checkIn->id))
            ->assertRedirect('/');
    }

    // =====================================================================
    // Privacy — the reason this plan exists
    // =====================================================================

    /** @test */
    public function no_pdf_is_ever_written_to_the_public_disk(): void
    {
        $this->useRecordingPrinter();
        $checkIn = $this->makeSignedCheckIn();

        $this->postJson('/api/readyToPrint', ['info' => ['id' => $checkIn->id, 'pets' => []]])->assertOk();

        $this->assertEmpty(
            Storage::disk('public')->allFiles(),
            'The printed summary carries owner PII, health notes and the signature — it must never be public.'
        );
    }

    /** @test */
    public function the_stored_document_path_is_relative(): void
    {
        $this->useRecordingPrinter();
        $checkIn = $this->makeSignedCheckIn();

        $this->postJson('/api/readyToPrint', ['info' => ['id' => $checkIn->id, 'pets' => []]])->assertOk();

        $path = $checkIn->fresh()->document_path;

        // An absolute URL is what broke re-prints across domains before this plan.
        $this->assertStringNotContainsString('http', $path);
        $this->assertStringStartsWith('pdfs/', $path);
    }

    /** @test */
    public function staff_can_view_a_stored_document(): void
    {
        $checkIn = $this->makeCheckIn();
        $this->givenStoredPdf($checkIn);

        $response = $this->actingAs($this->staff())
            ->get(route('pet-staff.check-in.document', $checkIn));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    /** @test */
    public function a_guest_cannot_view_a_stored_document(): void
    {
        $checkIn = $this->makeCheckIn();
        $this->givenStoredPdf($checkIn);

        $this->get(route('pet-staff.check-in.document', $checkIn))->assertRedirect();
    }

    /** @test */
    public function viewing_a_check_in_with_no_document_is_a_404(): void
    {
        $checkIn = $this->makeCheckIn();

        $this->actingAs($this->staff())
            ->get(route('pet-staff.check-in.document', $checkIn))
            ->assertNotFound();
    }

    // =====================================================================
    // Fake printer
    // =====================================================================

    /** @test */
    public function the_fake_printer_accepts_bytes_and_keeps_the_response_shape(): void
    {
        $result = (new FakePrintNodeService)->sendPrintJob("%PDF-1.4\nhello", ['title' => 'Drop-in: Luna']);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['fake']);
        foreach (['success', 'message', 'data'] as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    /** @test */
    public function a_fake_drop_in_completes_without_any_public_file(): void
    {
        config(['services.printnode.fake' => true]);
        $checkIn = $this->makeSignedCheckIn();

        $this->postJson('/api/readyToPrint', ['info' => ['id' => $checkIn->id, 'pets' => []]])->assertOk();

        $this->assertEmpty(Storage::disk('public')->allFiles());
        $this->assertNotNull($checkIn->fresh()->document_path);
    }

    // =====================================================================
    // Schema
    // =====================================================================

    /** @test */
    public function the_check_ins_table_uses_document_path_not_document_url(): void
    {
        $this->assertTrue(\Schema::hasColumn('check_ins', 'document_path'));
        $this->assertFalse(
            \Schema::hasColumn('check_ins', 'document_url'),
            'document_url stored an APP_URL-based absolute URL and is replaced by document_path.'
        );
    }
}
