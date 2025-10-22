<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;

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
        Schema::defaultStringLength(191);
        
        // Register CSP nonce Blade directive
        Blade::directive('cspnonce', function () {
            return "<?php echo request()->attributes->get('csp_nonce', ''); ?>";
        });
        
        // Register CSP nonce attribute directive
        Blade::directive('cspnonceattr', function () {
            return "<?php echo request()->attributes->get('csp_nonce') ? 'nonce=\"' . request()->attributes->get('csp_nonce') . '\"' : ''; ?>";
        });
    }
}
