<?php

// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EmailVerificationService;
use App\Services\CloudinaryService;
use App\Services\OcrService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // تسجيل الخدمات
        $this->app->singleton(EmailVerificationService::class, function ($app) {
            return new EmailVerificationService();
        });
        
        $this->app->singleton(CloudinaryService::class, function ($app) {
            return new CloudinaryService();
        });
        
        $this->app->singleton(OcrService::class, function ($app) {
            return new OcrService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
