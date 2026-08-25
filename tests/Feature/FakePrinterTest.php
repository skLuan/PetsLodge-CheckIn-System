<?php

namespace Tests\Feature;

use App\Services\FakePrintNodeService;
use App\Services\PrintNodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * PRINTNODE_FAKE — the local stand-in for the print API.
 *
 * PrintNode fetches the PDF from their own servers, so a localhost APP_URL can
 * never be reached and a local drop-in cannot complete against the real API.
 * These tests pin the two things that matter: the flag swaps the service, and
 * it is REFUSED in production (where faking would mean silently never printing).
 */
class FakePrinterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_real_service_is_used_by_default(): void
    {
        config(['services.printnode.fake' => false]);

        $service = app(PrintNodeService::class);

        $this->assertInstanceOf(PrintNodeService::class, $service);
        $this->assertNotInstanceOf(FakePrintNodeService::class, $service);
    }

    /** @test */
    public function the_flag_swaps_in_the_fake_service(): void
    {
        config(['services.printnode.fake' => true]);

        $this->assertInstanceOf(FakePrintNodeService::class, app(PrintNodeService::class));
    }

    /** @test */
    public function the_fake_still_satisfies_the_real_type_hint(): void
    {
        // Controllers type-hint PrintNodeService; the fake must remain a drop-in
        // replacement or every call site breaks.
        $this->assertInstanceOf(PrintNodeService::class, new FakePrintNodeService);
    }

    /** @test */
    public function faking_is_ignored_in_production(): void
    {
        config(['services.printnode.fake' => true]);
        app()->detectEnvironment(fn () => 'production');

        $service = app(PrintNodeService::class);

        $this->assertNotInstanceOf(
            FakePrintNodeService::class,
            $service,
            'Faking in production would report success while nothing ever printed.'
        );
    }

    /** @test */
    public function a_fake_print_reports_success_without_calling_printnode(): void
    {
        // The real service would build a Guzzle client and hit api.printnode.com;
        // reaching this assertion at all proves no HTTP call was attempted.
        $result = (new FakePrintNodeService)->sendPrintJob('http://localhost:8080/storage/pdfs/x.pdf', [
            'title' => 'Drop-in: Luna',
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['fake'], 'The response must be identifiable as simulated.');
        $this->assertStringContainsString('nothing was printed', $result['message']);
        $this->assertNotEmpty($result['data']['id']);
    }

    /** @test */
    public function a_fake_print_is_logged_loudly(): void
    {
        Log::spy();

        (new FakePrintNodeService)->sendPrintJob('http://localhost:8080/storage/pdfs/x.pdf');

        // Warning, not info — a fake print must never blend into the log noise.
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'FAKE MODE'));
    }

    /** @test */
    public function the_fake_response_shape_matches_the_real_one(): void
    {
        // DropInController reads ['success'] and ['data']; reprint reads ['message'].
        $result = (new FakePrintNodeService)->sendPrintJob('http://example.com/x.pdf');

        foreach (['success', 'message', 'data'] as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }
}
