<?php

namespace App\Providers;

use App\Services\NotificationCenter;
use Illuminate\Support\Facades\Blade;
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
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Blade::if('permission', function ($permission) {
            return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasPermission($permission));
        });

        View::composer('layouts.side_menu', function ($view) {
            if (auth()->check()) {
                $view->with(
                    'sectionBadges',
                    app(NotificationCenter::class)->sectionCounts(auth()->user())
                );
            }
        });
    }
}
