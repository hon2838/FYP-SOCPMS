<?php
namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('user-access', function ($user) {
            return $user && strtolower($user->user_type) === 'user';
        });

        Gate::define('admin-access', function ($user) {
            return $user && strtolower($user->user_type) === 'admin';
        });
    }
}
