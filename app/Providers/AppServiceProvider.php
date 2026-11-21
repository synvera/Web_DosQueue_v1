<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Maksimal 10 percobaan pengambilan tiket per menit per IP
        RateLimiter::for('ticket-creation', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Maksimal 10 percobaan login petugas per menit per IP (anti brute-force)
        RateLimiter::for('staff-login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Maksimal 30 request polling per menit per IP (cukup untuk interval 3-5 detik)
        RateLimiter::for('queue-polling', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}