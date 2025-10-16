<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\UserRequest;
use App\Policies\UserRequestPolicy;
use App\Modules\Ratings\Observers\RatingObserver;
use App\Models\Rating;
use App\Modules\Auth\Services\OtpChannel;
use App\Modules\Auth\Services\WhatsappOtpDriver;
use App\Modules\Payments\Services\PaymentProvider;
use App\Modules\Payments\Services\DummyProvider;
use App\Modules\Payments\Services\TapProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpChannel::class, WhatsappOtpDriver::class);
        $this->app->bind(PaymentProvider::class, TapProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Rating::observe(RatingObserver::class);

        Gate::policy(UserRequest::class, UserRequestPolicy::class);
        Gate::define('admin', fn ($user) => $user?->hasRole('ADMIN'));

        // Share brand settings globally in views
        try {
            $all = Setting::pluck('value','key')->toArray();
        } catch (\Throwable $e) {
            $all = [];
        }
        $disk = config('filesystems.default', 'public');
        $brandName = $all['brand.name'] ?? config('app.name', 'لوحة الإدارة');
        $logoUrl = !empty($all['brand.logo_path']) ? Storage::disk($disk)->url($all['brand.logo_path']) : null;
        $faviconUrl = !empty($all['brand.favicon_path']) ? Storage::disk($disk)->url($all['brand.favicon_path']) : null;
        View::share('appSettings', [
            'brand' => [
                'name' => $brandName,
                'logo_url' => $logoUrl,
                'favicon_url' => $faviconUrl,
            ],
        ]);
    }
}
