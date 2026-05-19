<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        if (getenv('VERCEL') || config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // MySQL 5.6 / MariaDB with utf8mb4: index key length max 767 bytes
        if (config('database.default') === 'mysql') {
            Schema::defaultStringLength(191);
        }

        // Auto-create a Super Admin conversation whenever a messageable user is created
        User::observe(UserObserver::class);
    }
}
