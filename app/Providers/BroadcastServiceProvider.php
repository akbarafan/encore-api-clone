<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register broadcasting routes with auth middleware for private channels
        Broadcast::routes(['middleware' => ['auth:api']]);

        // Also register public broadcasting routes without auth (untuk mobile)
        Broadcast::routes(['prefix' => 'public', 'middleware' => []]);

        require base_path('routes/channels.php');
    }
}
