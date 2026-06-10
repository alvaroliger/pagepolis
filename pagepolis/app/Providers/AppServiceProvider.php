<?php

namespace App\Providers;

use App\Listeners\HandleStripeWebhook;
use App\Models\Project;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

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
        Vite::prefetch(concurrency: 3);
        Gate::policy(Project::class, ProjectPolicy::class);

        // Lógica de negocio sobre el webhook de Stripe (mismo endpoint que Cashier).
        Event::listen(WebhookReceived::class, HandleStripeWebhook::class);
    }
}
