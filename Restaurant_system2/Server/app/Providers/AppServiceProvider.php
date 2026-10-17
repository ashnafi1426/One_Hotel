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
        // Clear config cache on every boot to ensure env vars are fresh
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            @unlink(base_path('bootstrap/cache/config.php'));
        }

        // Force SMTP mailer if Resend is somehow still configured
        // This handles edge case where env vars are cached or not updated
        if (config('mail.default') === 'resend') {
            config(['mail.default' => 'smtp']);
            \Illuminate\Support\Facades\Log::warning('[AppServiceProvider] Detected Resend mailer, forcing SMTP instead');
        }

        require_once app_path('Translations/helpers.php');

        view()->addNamespace('mail', resource_path('views/emails'));
    }
}

