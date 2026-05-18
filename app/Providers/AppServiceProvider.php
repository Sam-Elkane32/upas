<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
        // MySQL 5.6 / MariaDB with utf8mb4: index key length max 767 bytes
        if (config('database.default') === 'mysql') {
            Schema::defaultStringLength(191);
        }

        // Auto-create a Super Admin conversation whenever a messageable user is created
        User::observe(UserObserver::class);
    }
}
