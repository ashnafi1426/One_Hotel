<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\TenantContext::class, function () {
            return new \App\Services\TenantContext();
        });

        $this->app->singleton(\App\Services\Waiter\FloorResolverService::class,
            fn () => new \App\Services\Waiter\FloorResolverService()
        );

        $this->app->singleton(\App\Services\Waiter\ShiftResolverService::class,
            fn () => new \App\Services\Waiter\ShiftResolverService()
        );

        $this->app->singleton(\App\Services\Waiter\WaiterAvailabilityService::class,
            fn () => new \App\Services\Waiter\WaiterAvailabilityService()
        );

        $this->app->singleton(\App\Services\Waiter\WaiterSelectionEngine::class,
            fn () => new \App\Services\Waiter\WaiterSelectionEngine()
        );

        $this->app->singleton(\App\Services\Waiter\AssignmentStrategy::class, function ($app) {
            return new \App\Services\Waiter\AssignmentStrategy(
                $app->make(\App\Services\Waiter\WaiterAvailabilityService::class)
            );
        });

        $this->app->singleton(\App\Services\Waiter\DeliveryWorkloadService::class,
            fn () => new \App\Services\Waiter\DeliveryWorkloadService()
        );

        $this->app->singleton(\App\Services\Waiter\DeliveryNotificationService::class,
            fn () => new \App\Services\Waiter\DeliveryNotificationService()
        );

        $this->app->singleton(\App\Services\Waiter\AutomaticWaiterAssignmentService::class, function ($app) {
            return new \App\Services\Waiter\AutomaticWaiterAssignmentService(
                $app->make(\App\Services\Waiter\FloorResolverService::class),
                $app->make(\App\Services\Waiter\ShiftResolverService::class),
                $app->make(\App\Services\Waiter\WaiterSelectionEngine::class),
                $app->make(\App\Services\Waiter\DeliveryWorkloadService::class),
                $app->make(\App\Services\Waiter\DeliveryNotificationService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // PRODUCTION FIX: Force SMTP mailer on production
        // Render environment variables may not update properly, so enforce in code
        if (config('app.env') === 'production') {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => 'smtp.gmail.com',
                'mail.mailers.smtp.port' => 587,
                'mail.mailers.smtp.scheme' => 'tls',  // FIX: use 'scheme' not 'encryption'
                'mail.mailers.smtp.username' => env('MAIL_USERNAME', 'ashenafisileshi7@gmail.com'),
                'mail.mailers.smtp.password' => env('MAIL_PASSWORD', 'wqkxacketqwetpas'),
                'mail.from.address' => env('MAIL_FROM_ADDRESS', 'ashenafisileshi7@gmail.com'),
                'mail.from.name' => env('MAIL_FROM_NAME', 'HMS'),
            ]);
        }

        require_once app_path('Translations/helpers.php');

        view()->addNamespace('mail', resource_path('views/emails'));
    }
}

