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
    }
}
