<?php

namespace App\Providers;

use App\Models\TermsAndConditions;
use App\Services\FakePrintNodeService;
use App\Services\PrintNodeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->bindPrinter();
    }

    /**
     * Resolve the printer: the real PrintNode API, or a local stand-in.
     *
     * PrintNode pulls the PDF from their cloud, so a local `APP_URL` is
     * unreachable to them and no amount of valid credentials makes a local
     * drop-in complete. `PRINTNODE_FAKE=true` swaps in a logger instead.
     *
     * Faking in production would mean silently never printing, so it is refused
     * there and the real service is used regardless of the flag.
     */
    private function bindPrinter(): void
    {
        $this->app->bind(PrintNodeService::class, function ($app) {
            if (config('services.printnode.fake') && ! $app->environment('production')) {
                return new FakePrintNodeService;
            }

            if (config('services.printnode.fake')) {
                Log::warning('PRINTNODE_FAKE is set in production and is being IGNORED — using the real PrintNode API.');
            }

            return new PrintNodeService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->composeTermsAndConditionsPopup();
    }

    /**
     * Feed the active Terms & Conditions version into the check-in popup.
     *
     * Using a view composer keeps every page that includes the popup free of
     * controller changes — the partial asks for `$activeTerms` and gets it.
     */
    private function composeTermsAndConditionsPopup(): void
    {
        View::composer('components.pop-ups.terms-conditions', function ($view) {
            $terms = null;

            // The table is missing during a fresh install / before migrations run;
            // the popup must degrade rather than break the whole check-in page.
            if (Schema::hasTable('terms_and_conditions')) {
                $terms = TermsAndConditions::active()->first();
            }

            if (! $terms) {
                Log::warning('No active Terms & Conditions version found; the check-in popup will render empty.');
            }

            $view->with('activeTerms', $terms);
        });
    }
}
