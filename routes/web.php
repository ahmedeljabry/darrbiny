<?php

use App\Http\Controllers\Admin\AdvancedReportsController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\PlansController;
use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::middleware(['web'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('guest')->group(function () {
            Route::get('/login', [AdminLoginController::class, 'show'])->name('login');
            Route::post('/login', [AdminLoginController::class, 'login'])->name('login.post')->middleware('throttle:6,1');
        });

        // Logout for authenticated users
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout')->middleware('auth');

        Route::middleware(['auth', 'ensure.admin'])->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard')->middleware('can:view_admin');
            Route::get('/course-details', [DashboardController::class, 'courseDetails'])->name('course.details')->middleware('can:manage_plans');

            Route::middleware('can:manage_plans')->group(function () {
                // Bookings Management
                Route::get('/bookings', [\App\Http\Controllers\Admin\BookingsController::class, 'index'])->name('bookings.index');
                Route::delete('/bookings/bulk-destroy', [\App\Http\Controllers\Admin\BookingsController::class, 'bulkDestroy'])->name('bookings.bulk-destroy');
                Route::get('/bookings/{id}', [\App\Http\Controllers\Admin\BookingsController::class, 'show'])->name('bookings.show');
                Route::post('/bookings', [\App\Http\Controllers\Admin\BookingsController::class, 'store'])->name('bookings.store');
                Route::put('/bookings/{id}/status', [\App\Http\Controllers\Admin\BookingsController::class, 'updateStatus'])->name('bookings.update-status');
                Route::post('/bookings/{id}/cancel', [\App\Http\Controllers\Admin\BookingsController::class, 'cancel'])
                    ->name('bookings.cancel')
                    ->middleware('can:cancel_courses');
                Route::delete('/bookings/{id}', [\App\Http\Controllers\Admin\BookingsController::class, 'destroy'])->name('bookings.destroy');

                Route::resource('plans', PlansController::class)->names('plans');
                Route::post('/plans/{id}/move-up', [PlansController::class, 'moveUp'])->name('plans.move-up');
                Route::post('/plans/{id}/move-down', [PlansController::class, 'moveDown'])->name('plans.move-down');
                Route::put('/plans/{planId}/requests/{requestId}/status', [PlansController::class, 'updateRequestStatus'])->name('plans.requests.update-status');
                Route::get('/plans/{planId}/schedule', [\App\Http\Controllers\Admin\PlanScheduleController::class, 'index'])->name('plans.schedule.index');
                Route::post('/plans/{planId}/schedule', [\App\Http\Controllers\Admin\PlanScheduleController::class, 'store'])->name('plans.schedule.store');
                Route::put('/plans/schedule/{id}', [\App\Http\Controllers\Admin\PlanScheduleController::class, 'update'])->name('plans.schedule.update');
                Route::delete('/plans/schedule/{id}', [\App\Http\Controllers\Admin\PlanScheduleController::class, 'destroy'])->name('plans.schedule.destroy');

                // Cancellation Requests
                Route::get('/cancellation-requests', [\App\Http\Controllers\Admin\CancellationRequestsController::class, 'index'])->name('cancellation-requests.index');
                Route::get('/cancellation-requests/{id}', [\App\Http\Controllers\Admin\CancellationRequestsController::class, 'show'])->name('cancellation-requests.show');
                Route::post('/cancellation-requests/{id}/approve', [\App\Http\Controllers\Admin\CancellationRequestsController::class, 'approve'])
                    ->name('cancellation-requests.approve')
                    ->middleware('can:cancel_courses');
                Route::post('/cancellation-requests/{id}/reject', [\App\Http\Controllers\Admin\CancellationRequestsController::class, 'reject'])
                    ->name('cancellation-requests.reject')
                    ->middleware('can:cancel_courses');
            });

            Route::middleware('can:manage_users')->group(function () {
                Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
                Route::get('/users/create', [AdminUsersController::class, 'create'])->name('users.create');
                Route::post('/users', [AdminUsersController::class, 'store'])->name('users.store');
                Route::get('/users/{id}', [AdminUsersController::class, 'show'])->name('users.show');
                Route::get('/users/{id}/edit', [AdminUsersController::class, 'edit'])->name('users.edit');
                Route::put('/users/{id}', [AdminUsersController::class, 'update'])->name('users.update');
                Route::post('/users/{id}/freeze', [AdminUsersController::class, 'freeze'])->name('users.freeze');
                Route::delete('/users/{id}/force', [AdminUsersController::class, 'forceDestroy'])->name('users.force-destroy');
                Route::post('/users/{id}/ban', [AdminUsersController::class, 'ban'])->name('users.ban');
                Route::post('/users/{id}/unban', [AdminUsersController::class, 'unban'])->name('users.unban');
                Route::post('/users/reset-all', [AdminUsersController::class, 'resetAll'])->name('users.reset-all');
                Route::post('/users/bulk-action', [AdminUsersController::class, 'bulkAction'])->name('users.bulk-action');
                Route::post('/users/{id}/trainer-profile/approve', [AdminUsersController::class, 'approveTrainerProfile'])
                    ->name('users.trainer-profile.approve')
                    ->middleware('can:verify_trainers');
                Route::post('/users/{id}/trainer-profile/reject', [AdminUsersController::class, 'rejectTrainerProfile'])
                    ->name('users.trainer-profile.reject')
                    ->middleware('can:verify_trainers');
                Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');
            });

            Route::middleware('can:manage_settings')->group(function () {
                Route::get('/content', [AdminContentController::class, 'index'])->name('content.index');
                Route::post('/content', [AdminContentController::class, 'update'])->name('content.update');
                Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
                Route::delete('/media/{id}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
            });

            Route::middleware('can:manage_payments')->group(function () {
                Route::get('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionsController::class, 'index'])->name('subscriptions.index');
                Route::get('/payments', [\App\Http\Controllers\Admin\PaymentsAdminController::class, 'index'])->name('payments.index');
                Route::get('/app-wallet-account', [\App\Http\Controllers\Admin\AppWalletAccountController::class, 'index'])->name('app-wallet-account.index');
                Route::post('/app-wallet-account/transactions', [\App\Http\Controllers\Admin\AppWalletAccountController::class, 'store'])->name('app-wallet-account.transactions.store');
                Route::get('/app-expenses', [\App\Http\Controllers\Admin\AppExpensesController::class, 'index'])->name('app-expenses.index');
                Route::post('/app-expenses', [\App\Http\Controllers\Admin\AppExpensesController::class, 'store'])->name('app-expenses.store');
                Route::put('/app-expenses/{id}', [\App\Http\Controllers\Admin\AppExpensesController::class, 'update'])->name('app-expenses.update');
                Route::delete('/app-expenses/{id}', [\App\Http\Controllers\Admin\AppExpensesController::class, 'destroy'])->name('app-expenses.destroy');
            });

            Route::middleware('can:manage_reports')->group(function () {
                Route::get('/reports', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('reports.index');
                Route::get('/reports/sales', [\App\Http\Controllers\Admin\ReportsController::class, 'sales'])->name('reports.sales');
                Route::get('/reports/payments', [\App\Http\Controllers\Admin\ReportsController::class, 'payments'])->name('reports.payments');
                Route::get('/reports/subscriptions', [\App\Http\Controllers\Admin\ReportsController::class, 'subscriptions'])->name('reports.subscriptions');
                Route::get('/reports/vat', [\App\Http\Controllers\Admin\ReportsController::class, 'vat'])->name('reports.vat');
                Route::get('/reports/app-profits', [AdvancedReportsController::class, 'appProfits'])->name('reports.app-profits');
                Route::get('/reports/rejected-progress', [AdvancedReportsController::class, 'rejectedProgress'])->name('reports.rejected-progress');
                Route::get('/reports/wallet-balances', [AdvancedReportsController::class, 'walletBalances'])->name('reports.wallet-balances');
            });
            Route::get('/reports/completed-payouts', [AdvancedReportsController::class, 'completedPayouts'])
                ->name('reports.completed-payouts')
                ->middleware('can:manage_payouts');

            Route::middleware('can:manage_geo')->group(function () {
                Route::get('/geo', [\App\Http\Controllers\Admin\GeoAdminController::class, 'index'])->name('geo.index');
                Route::get('/geo/countries/create', [\App\Http\Controllers\Admin\GeoAdminController::class, 'createCountry'])->name('geo.countries.create');
                Route::get('/geo/countries/{id}/edit', [\App\Http\Controllers\Admin\GeoAdminController::class, 'editCountry'])->name('geo.countries.edit');
                Route::post('/geo/countries', [\App\Http\Controllers\Admin\GeoAdminController::class, 'storeCountry'])->name('geo.countries.store');
                Route::put('/geo/countries/{id}', [\App\Http\Controllers\Admin\GeoAdminController::class, 'updateCountry'])->name('geo.countries.update');
                Route::delete('/geo/countries/{id}', [\App\Http\Controllers\Admin\GeoAdminController::class, 'destroyCountry'])->name('geo.countries.destroy');
            });

            Route::middleware('can:manage_ratings')->group(function () {
                Route::get('/ratings', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'index'])->name('ratings.index');
                Route::get('/ratings/create', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'create'])->name('ratings.create');
                Route::post('/ratings', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'store'])->name('ratings.store');
                Route::get('/ratings/{rating}/edit', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'edit'])->name('ratings.edit');
                Route::put('/ratings/{rating}', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'update'])->name('ratings.update');
                Route::delete('/ratings/{rating}', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'destroy'])->name('ratings.destroy');
            });

            Route::middleware('can:manage_wallets')->group(function () {
                Route::get('/wallets', [\App\Http\Controllers\Admin\WalletsController::class, 'index'])->name('wallets.index');
                Route::post('/wallets', [\App\Http\Controllers\Admin\WalletsController::class, 'store'])->name('wallets.store');
                Route::put('/wallets/{id}', [\App\Http\Controllers\Admin\WalletsController::class, 'update'])->name('wallets.update');
                Route::get('/gateway-wallets/{gateway}', [\App\Http\Controllers\Admin\GatewayWalletsController::class, 'show'])
                    ->whereIn('gateway', ['tap', 'tabby', 'tamara'])
                    ->name('gateway-wallets.show');
                Route::post('/gateway-wallets/{gateway}/transactions', [\App\Http\Controllers\Admin\GatewayWalletsController::class, 'store'])
                    ->whereIn('gateway', ['tap', 'tabby', 'tamara'])
                    ->name('gateway-wallets.transactions.store');

                // Wallet Transactions
                Route::get('/wallet-transactions', [\App\Http\Controllers\Admin\WalletTransactionsController::class, 'index'])->name('wallet-transactions.index');
                Route::get('/wallet-transactions/{id}', [\App\Http\Controllers\Admin\WalletTransactionsController::class, 'show'])->name('wallet-transactions.show');
                Route::post('/wallet-transactions/{id}/approve', [\App\Http\Controllers\Admin\WalletTransactionsController::class, 'approve'])->name('wallet-transactions.approve');
                Route::post('/wallet-transactions/{id}/reject', [\App\Http\Controllers\Admin\WalletTransactionsController::class, 'reject'])->name('wallet-transactions.reject');

                // Withdrawal Requests
                Route::get('/withdrawal-requests', [\App\Http\Controllers\Admin\WithdrawalRequestsController::class, 'index'])->name('withdrawal-requests.index');
                Route::get('/withdrawal-requests/{id}', [\App\Http\Controllers\Admin\WithdrawalRequestsController::class, 'show'])->name('withdrawal-requests.show');
                Route::post('/withdrawal-requests/{id}/approve', [\App\Http\Controllers\Admin\WithdrawalRequestsController::class, 'approve'])->name('withdrawal-requests.approve');
                Route::post('/withdrawal-requests/{id}/reject', [\App\Http\Controllers\Admin\WithdrawalRequestsController::class, 'reject'])->name('withdrawal-requests.reject');
            });

            Route::middleware('can:manage_notifications')->group(function () {
                Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'index'])->name('notifications.index');
                Route::get('/notifications/view', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'view'])->name('notifications.view');
                Route::get('/notifications/users', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'users'])->name('notifications.users');
                Route::get('/notifications/{id}', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'show'])->name('notifications.show');
                Route::post('/notifications/{id}/read', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'markAsRead'])->name('notifications.mark-read');
                Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'markAllRead'])->name('notifications.mark-all-read');
                Route::post('/notifications', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'send'])->name('notifications.send');

                // Messages
                Route::get('/messages', [\App\Http\Controllers\Admin\MessagesController::class, 'index'])->name('messages.index');
                Route::get('/messages/all', [\App\Http\Controllers\Admin\MessagesController::class, 'messages'])->name('messages.messages');
                Route::get('/messages/{id}', [\App\Http\Controllers\Admin\MessagesController::class, 'show'])->name('messages.show');

                // Support tickets
                Route::get('/support', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'index'])->name('support.index');
                Route::get('/support/{id}', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'show'])->name('support.show');
                Route::post('/support/{id}/reply', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'reply'])->name('support.reply');
            });

            Route::middleware('can:manage_rewards')->group(function () {
                // Prizes Management
                Route::resource('prizes', \App\Http\Controllers\Admin\PrizesController::class)->names('prizes');
                Route::get('/prize-redemptions', [\App\Http\Controllers\Admin\PrizeRedemptionsController::class, 'index'])->name('prize-redemptions.index');
                Route::get('/prize-redemptions/{id}', [\App\Http\Controllers\Admin\PrizeRedemptionsController::class, 'show'])->name('prize-redemptions.show');
                Route::post('/prize-redemptions/{id}/approve', [\App\Http\Controllers\Admin\PrizeRedemptionsController::class, 'approve'])->name('prize-redemptions.approve');
                Route::post('/prize-redemptions/{id}/reject', [\App\Http\Controllers\Admin\PrizeRedemptionsController::class, 'reject'])->name('prize-redemptions.reject');
                Route::get('/rewards/points', [AdvancedReportsController::class, 'pointsBalances'])->name('rewards.points');
                Route::get('/rewards/redemptions-report', [AdvancedReportsController::class, 'rewardRedemptions'])->name('rewards.redemptions-report');
            });
            // Roles & permissions
            Route::middleware('can:manage_roles')->group(function () {
                Route::get('/roles', [\App\Http\Controllers\Admin\RolesController::class, 'index'])->name('roles.index');
                Route::post('/roles', [\App\Http\Controllers\Admin\RolesController::class, 'store'])->name('roles.store');
                Route::put('/roles/{id}', [\App\Http\Controllers\Admin\RolesController::class, 'update'])->name('roles.update');
                Route::delete('/roles/{id}', [\App\Http\Controllers\Admin\RolesController::class, 'destroy'])->name('roles.destroy');
                Route::get('/users/{id}/roles', [\App\Http\Controllers\Admin\UserRolesController::class, 'edit'])->name('users.roles.edit');
                Route::put('/users/{id}/roles', [\App\Http\Controllers\Admin\UserRolesController::class, 'update'])->name('users.roles.update');
            });

            Route::middleware('can:manage_permissions')->group(function () {
                Route::get('/permissions', [\App\Http\Controllers\Admin\PermissionsController::class, 'index'])->name('permissions.index');
                Route::post('/permissions', [\App\Http\Controllers\Admin\PermissionsController::class, 'store'])->name('permissions.store');
                Route::delete('/permissions/{id}', [\App\Http\Controllers\Admin\PermissionsController::class, 'destroy'])->name('permissions.destroy');
            });

            Route::middleware('can:manage_settings')->group(function () {
                // Settings
                Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
                Route::post('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
                Route::post('/settings/how-it-works', [\App\Http\Controllers\Admin\HowItWorksController::class, 'update'])->name('settings.howitworks.update');
            });
        });
    });

// Allow exiting impersonation even بعد تسجيل الدخول كـ مستخدم
Route::match(['get', 'post'], '/admin/impersonation/stop', [ImpersonationController::class, 'stop'])
    ->middleware('auth')
    ->name('admin.impersonation.stop');

if (app()->environment('local')) {
    Route::get('/admin/dev-login', function () {
        $admin = User::role('ADMIN')->first();
        if (! $admin) {
            abort(404, 'No admin user');
        }
        Auth::login($admin);

        return redirect()->route('admin.dashboard');
    });
}
