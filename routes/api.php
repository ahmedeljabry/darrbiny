<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('otp', fn ($request) => [Limit::perMinute(3)->by($request->ip())]);
RateLimiter::for('auth', fn ($request) => [Limit::perMinute(30)->by($request->user()?->id ?? $request->ip())]);
RateLimiter::for('default', fn ($request) => [Limit::perMinute(60)->by($request->user()?->id ?? $request->ip())]);

Route::prefix('v1')->middleware(['correlation', 'json.envelope', 'sanitize'])->group(function () {

        // Auth
        Route::prefix('auth')->middleware('throttle:otp')->group(function () {
            Route::post('/request-otp', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'requestOtp']);
            Route::post('/verify-otp', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'verifyOtp']);
        });

        Route::prefix('auth')->middleware('throttle:auth')->group(function () {
            Route::middleware('auth:sanctum')->group(function () {
                Route::get('/me', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'me']);
                Route::post('/logout', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'logout']);
            });
            Route::post('/refresh', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'refresh']);
        });

        Route::prefix('home')->group(function () {
            Route::get('/' , \App\Modules\Home\Http\Controllers\HomeController::class);
            Route::post('favorites', [\App\Modules\Favorites\Http\Controllers\FavoriteController::class, 'store']);
            Route::delete('favorites/{trainerId}', [\App\Modules\Favorites\Http\Controllers\FavoriteController::class, 'destroy']);
        });

        // Catalog
        Route::get('/countries', [\App\Modules\Catalog\Http\Controllers\GeoController::class, 'countries']);
        Route::get('/cities', [\App\Modules\Catalog\Http\Controllers\GeoController::class, 'cities']);
        Route::get('/plans', [\App\Modules\Catalog\Http\Controllers\PlanController::class, 'index']);

        // User Requests
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/user-requests', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'store']);
            Route::get('/user-requests/{id}', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'show']);
            Route::get('/user-requests', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'index']);

            // Offers
            Route::get('/user-requests/{id}/offers', [\App\Modules\Offers\Http\Controllers\OfferController::class, 'listForRequest']);
            Route::post('/trainer/offers', [\App\Modules\Offers\Http\Controllers\OfferController::class, 'store']);
            Route::post('/offers/{id}/accept', [\App\Modules\Offers\Http\Controllers\OfferController::class, 'accept']);

            // Payments
            Route::post('/payments/reservation', [\App\Modules\Payments\Http\Controllers\PaymentController::class, 'reservation']);
            Route::post('/payments/plan', [\App\Modules\Payments\Http\Controllers\PaymentController::class, 'plan']);
            Route::post('/payments/webhook/{provider}', [\App\Modules\Payments\Http\Controllers\PaymentController::class, 'webhook'])->withoutMiddleware('auth:sanctum');

            // Training
            Route::post('/training-days', [\App\Modules\Training\Http\Controllers\TrainingDayController::class, 'store']);
            Route::get('/training-days', [\App\Modules\Training\Http\Controllers\TrainingDayController::class, 'index']);
            Route::post('/training-days/{id}/approve', [\App\Modules\Training\Http\Controllers\TrainingDayController::class, 'approve']);
            Route::post('/training-days/{id}/reject', [\App\Modules\Training\Http\Controllers\TrainingDayController::class, 'reject']);

            // Completion & Payouts
            Route::post('/user-requests/{id}/complete', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'complete']);
            Route::get('/payouts', [\App\Modules\Payouts\Http\Controllers\PayoutController::class, 'index']);

            // Ratings
            Route::post('/ratings', [\App\Modules\Ratings\Http\Controllers\RatingController::class, 'store']);
            Route::get('/ratings', [\App\Modules\Ratings\Http\Controllers\RatingController::class, 'index']);

            // Referrals & Rewards
            Route::get('/me/referral', [\App\Modules\Referrals\Http\Controllers\ReferralController::class, 'me']);
            Route::get('/rewards', [\App\Modules\Rewards\Http\Controllers\RewardController::class, 'index']);
            Route::post('/rewards/redeem', [\App\Modules\Rewards\Http\Controllers\RewardController::class, 'redeem']);


        });

    });
