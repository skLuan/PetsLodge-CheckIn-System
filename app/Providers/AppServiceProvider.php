<?php

namespace App\Providers;

use App\Models\TermsAndConditions;
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
        //
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
