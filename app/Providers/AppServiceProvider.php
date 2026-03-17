<?php

namespace App\Providers;

use App\Models\DrivePackage;
use Illuminate\Support\Facades\Schema;
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
        if (app()->runningInConsole()) {
            return;
        }

        if (Schema::hasTable('drive_packages') && Schema::hasColumn('drive_packages', 'offer_ends_at')) {
            DrivePackage::query()
                ->where('status', 'active')
                ->whereNotNull('offer_ends_at')
                ->where('offer_ends_at', '<=', now())
                ->update(['status' => 'deactive']);
        }
    }
}
