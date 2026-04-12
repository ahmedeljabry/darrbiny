<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\UserRequest;
use App\Policies\UserRequestPolicy;
use App\Modules\Ratings\Observers\RatingObserver;
use App\Models\Rating;
use App\Modules\Payments\Services\PaymentProvider;
use App\Modules\Payments\Services\DummyProvider;
use App\Modules\Payments\Services\TapProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use App\Models\Setting;
use App\Support\StorageUrl;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, TapProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Rating::observe(RatingObserver::class);

        Gate::policy(UserRequest::class, UserRequestPolicy::class);
        Gate::define('admin', fn ($user) => $user?->hasRole('ADMIN'));

        // Share brand settings globally in views
        try {
            $all = Setting::pluck('value','key')->toArray();
        } catch (\Throwable $e) {
            $all = [];
        }
        $brandName = $all['brand.name'] ?? config('app.name', 'لوحة الإدارة');
        $logoUrl = StorageUrl::make($all['brand.logo_path'] ?? null);
        $faviconUrl = StorageUrl::make($all['brand.favicon_path'] ?? null);
        View::share('appSettings', [
            'brand' => [
                'name' => $brandName,
                'logo_url' => $logoUrl,
                'favicon_url' => $faviconUrl,
            ],
        ]);
    }
}
