<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Vendor::class => \App\Policies\VendorPolicy::class,
        \App\Models\Plan::class => \App\Policies\PlanPolicy::class,
        \App\Models\Subscription::class => \App\Policies\SubscriptionPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Payment::class => \App\Policies\PaymentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super Admin Gate - bypasses all permissions
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });

        // Define additional gates
        Gate::define('access-admin', function ($user) {
            return $user->hasRole(['super_admin', 'admin']);
        });

        Gate::define('manage-vendors', function ($user) {
            return $user->hasRole(['super_admin', 'admin']);
        });

        Gate::define('manage-plans', function ($user) {
            return $user->hasRole(['super_admin', 'admin']);
        });

        Gate::define('manage-subscriptions', function ($user) {
            return $user->hasRole(['super_admin', 'admin']);
        });

        Gate::define('manage-payments', function ($user) {
            return $user->hasRole(['super_admin', 'admin']);
        });

        Gate::define('manage-users', function ($user) {
            return $user->hasRole(['super_admin', 'admin']);
        });

        Gate::define('view-reports', function ($user) {
            return $user->hasRole(['super_admin', 'admin']);
        });

        Gate::define('manage-settings', function ($user) {
            return $user->hasRole(['super_admin', 'admin']);
        });
    }
}
