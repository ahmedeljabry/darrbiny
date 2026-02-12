<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('otp', fn ($request) => [Limit::perMinute(3)->by($request->ip())]);
RateLimiter::for('auth', fn ($request) => [Limit::perMinute(30)->by($request->user()?->id ?? $request->ip())]);
RateLimiter::for('default', fn ($request) => [Limit::perMinute(60)->by($request->user()?->id ?? $request->ip())]);

Route::prefix('v1')->middleware(['correlation', 'json.envelope', 'sanitize'])->group(function () {

        // Auth
        Route::prefix('auth')->middleware('throttle:auth')->group(function () {
            Route::post('/register', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'register']);
            Route::post('/login', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'login']);
        });

        Route::prefix('auth')->middleware('throttle:otp')->group(function () {
            Route::post('/request-otp', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'requestOtp']);
            Route::post('/verify-otp', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'verifyOtp']);
        });

        Route::prefix('auth')->middleware('throttle:auth')->group(function () {
            Route::middleware('auth:sanctum')->group(function () {
                Route::get('/me', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'me']);
                Route::post('/logout', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'logout']);
                Route::post('/profile', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'updateProfile']);
                Route::post('/change-password', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'changePassword']);
                Route::get('/bank-account', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'getBankAccount']);
                Route::post('/bank-account', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'updateBankAccount']);
                Route::post('/delete-account', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'deleteAccount']);
            });
            Route::post('/refresh', [\App\Modules\Auth\Http\Controllers\AuthController::class, 'refresh']);
        });

        Route::prefix('home')->group(function () {
            Route::get('/' , \App\Modules\Home\Http\Controllers\HomeController::class);
            Route::post('favorites', [\App\Modules\Favorites\Http\Controllers\FavoriteController::class, 'store'])->middleware('auth:sanctum');
            Route::delete('favorites/{trainerId}', [\App\Modules\Favorites\Http\Controllers\FavoriteController::class, 'destroy']);
        });

        // Support Tickets (public endpoint for creating tickets)
        Route::post('/support-tickets', [\App\Modules\Support\Http\Controllers\SupportTicketController::class, 'store']);

        // Support Tickets (authenticated endpoints)
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/support-tickets', [\App\Modules\Support\Http\Controllers\SupportTicketController::class, 'index']);
            Route::get('/support-tickets/{id}', [\App\Modules\Support\Http\Controllers\SupportTicketController::class, 'show']);
        });

        // Catalog
        Route::get('/countries', [\App\Modules\Catalog\Http\Controllers\GeoController::class, 'countries']);
        Route::get('/cities', [\App\Modules\Catalog\Http\Controllers\GeoController::class, 'cities']);
        Route::get('/plans', [\App\Modules\Catalog\Http\Controllers\PlanController::class, 'index']);
        Route::get('/plans/{plan}', [\App\Modules\Catalog\Http\Controllers\PlanController::class, 'show']);
        Route::get('/trainers', [\App\Modules\Catalog\Http\Controllers\TrainerController::class, 'index']);

        // Settings
        Route::get('/settings/fees', [\App\Modules\Settings\Http\Controllers\SettingsController::class, 'fees']);
        Route::get('/settings/roles', [\App\Modules\Settings\Http\Controllers\SettingsController::class, 'roles']);
        Route::get('/settings/pages', [\App\Modules\Settings\Http\Controllers\SettingsController::class, 'pages']);

        // User Routes
        Route::prefix('user')->middleware('auth:sanctum')->group(function () {
            Route::post('/subscriptions/{id}/cancel', [\App\Modules\Requests\Http\Controllers\CancellationController::class, 'cancel']);
            Route::get('/subscriptions/{id}/cancellation-status', [\App\Modules\Requests\Http\Controllers\CancellationController::class, 'status']);

            // Schedule endpoints
            Route::get('/subscriptions/schedule', [\App\Modules\Requests\Http\Controllers\ScheduleController::class, 'index']);
            Route::post('/subscriptions/{id}/schedule/{dayNumber}/check', [\App\Modules\Requests\Http\Controllers\ScheduleController::class, 'check']);
            Route::post('/subscriptions/{id}/schedule/{dayNumber}/uncheck', [\App\Modules\Requests\Http\Controllers\ScheduleController::class, 'uncheck']);
            Route::post('/subscriptions/{id}/schedule/{dayNumber}/accept', [\App\Modules\Requests\Http\Controllers\ScheduleController::class, 'accept']);
            Route::post('/subscriptions/{id}/schedule/{dayNumber}/reject', [\App\Modules\Requests\Http\Controllers\ScheduleController::class, 'reject']);
            Route::post('/subscriptions/{id}/schedule/{dayNumber}/rate', [\App\Modules\Requests\Http\Controllers\ScheduleController::class, 'rate']);
        });

        // Trainer (Captain) profile/account endpoints
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/captain/account-details', [\App\Modules\Trainers\Http\Controllers\CaptainAccountController::class, 'show']);
            Route::post('/captain/account-details', [\App\Modules\Trainers\Http\Controllers\CaptainAccountController::class, 'store']);
        });

        // User Requests
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/user-requests', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'store']);
            Route::get('/user-requests/offers', [\App\Modules\Offers\Http\Controllers\OfferController::class, 'listForUserRequests']);
            Route::get('/user-requests/{id}', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'show']);
            Route::get('/user-requests', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'index']);
            Route::get('/subscriptions', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'subscriptions']);
            Route::get('/trainers/{trainerId}/bookings', [\App\Modules\Requests\Http\Controllers\UserRequestController::class, 'trainerBookings']);

            // Offers
            Route::get('/user-requests/{id}/offers', [\App\Modules\Offers\Http\Controllers\OfferController::class, 'listForRequest']);
            Route::post('/trainer/offers', [\App\Modules\Offers\Http\Controllers\OfferController::class, 'store']);
            Route::put('/trainer/offers/{id}', [\App\Modules\Offers\Http\Controllers\OfferController::class, 'update']);
            Route::post('/offers/{id}/accept', [\App\Modules\Offers\Http\Controllers\OfferController::class, 'accept']);

            // Payments
            Route::get('/user-requests/{id}/payment-details', [\App\Modules\Payments\Http\Controllers\PaymentController::class, 'paymentDetails']);
            Route::post('/payments/plan', [\App\Modules\Payments\Http\Controllers\PaymentController::class, 'plan']);

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
            Route::get('/prizes', [\App\Modules\Rewards\Http\Controllers\RewardController::class, 'index']);
            Route::post('/prizes/request', [\App\Modules\Rewards\Http\Controllers\RewardController::class, 'requestPrize']);

            // Messages & Conversations
            Route::get('/conversations', [\App\Modules\Messages\Http\Controllers\ConversationController::class, 'index']);
            Route::post('/conversations', [\App\Modules\Messages\Http\Controllers\ConversationController::class, 'store']);
            Route::get('/conversations/{id}', [\App\Modules\Messages\Http\Controllers\ConversationController::class, 'show']);
            Route::delete('/conversations/{id}', [\App\Modules\Messages\Http\Controllers\ConversationController::class, 'destroy']);
            Route::get('/conversations/{id}/messages', [\App\Modules\Messages\Http\Controllers\MessageController::class, 'index']);
            Route::post('/conversations/{id}/messages', [\App\Modules\Messages\Http\Controllers\MessageController::class, 'store']);
            Route::post('/conversations/{id}/messages/read', [\App\Modules\Messages\Http\Controllers\MessageController::class, 'markAllRead']);
            Route::post('/messages/{id}/read', [\App\Modules\Messages\Http\Controllers\MessageController::class, 'markRead']);
            Route::get('/messages/search', [\App\Modules\Messages\Http\Controllers\MessageController::class, 'search']);

            // Wallet
            Route::get('/wallet', [\App\Modules\Wallet\Http\Controllers\WalletController::class, 'index']);
            Route::get('/wallet/transactions', [\App\Modules\Wallet\Http\Controllers\WalletController::class, 'transactions']);
            Route::post('/wallet/topup-request', [\App\Modules\Wallet\Http\Controllers\WalletController::class, 'requestTopup']);
            Route::post('/wallet/deduct', [\App\Modules\Wallet\Http\Controllers\WalletController::class, 'deduct']);

            // Notifications
            Route::get('/notifications', [\App\Modules\Notifications\Http\Controllers\NotificationController::class, 'index']);
            Route::get('/notifications/unread-count', [\App\Modules\Notifications\Http\Controllers\NotificationController::class, 'unreadCount']);
            Route::post('/notifications/{id}/read', [\App\Modules\Notifications\Http\Controllers\NotificationController::class, 'markAsRead']);
            Route::post('/notifications/read-all', [\App\Modules\Notifications\Http\Controllers\NotificationController::class, 'markAllAsRead']);


        });

    });
