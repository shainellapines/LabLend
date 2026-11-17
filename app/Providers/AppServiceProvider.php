<?php

namespace App\Providers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
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
        // View composer: runs every time layouts/sidebar.blade.php is rendered and hands it $pendingCount.
        // This keeps the query out of the Blade view (MVC) while every page still gets the badge.
        View::composer('layouts.sidebar', function ($view) {
            $view->with('pendingCount', auth()->user()?->isAdmin()
                ? Reservation::where('status', ReservationStatus::Pending)->count()
                : 0);
        });

        // Same idea for the bell in the top bar (FR-N2): unread count + the 8 newest notifications.
        View::composer('layouts.topbar', function ($view) {
            $user = auth()->user();
            $view->with([
                'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
                'latestNotifications' => $user ? $user->notifications()->limit(8)->get() : collect(),
            ]);
        });
    }
}
