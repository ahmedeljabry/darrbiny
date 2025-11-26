<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Admin\PlansController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\ContentController as AdminContentController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\AdvancedReportsController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

Route::get('/', function () {
    return view('welcome');
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
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            Route::get('/course-details', [DashboardController::class, 'courseDetails'])->name('course.details');

            // Bookings Management
            Route::get('/bookings', [\App\Http\Controllers\Admin\BookingsController::class, 'index'])->name('bookings.index');
            Route::get('/bookings/{id}', [\App\Http\Controllers\Admin\BookingsController::class, 'show'])->name('bookings.show');
            Route::post('/bookings', [\App\Http\Controllers\Admin\BookingsController::class, 'store'])->name('bookings.store');
            Route::put('/bookings/{id}/status', [\App\Http\Controllers\Admin\BookingsController::class, 'updateStatus'])->name('bookings.update-status');
            Route::delete('/bookings/{id}', [\App\Http\Controllers\Admin\BookingsController::class, 'destroy'])->name('bookings.destroy');

            Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
            Route::get('/users/create', [AdminUsersController::class, 'create'])->name('users.create');
            Route::post('/users', [AdminUsersController::class, 'store'])->name('users.store');
            Route::get('/users/{id}', [AdminUsersController::class, 'show'])->name('users.show');
            Route::get('/users/{id}/edit', [AdminUsersController::class, 'edit'])->name('users.edit');
            Route::put('/users/{id}', [AdminUsersController::class, 'update'])->name('users.update');
            Route::post('/users/{id}/freeze', [AdminUsersController::class, 'freeze'])->name('users.freeze');
            Route::post('/users/{id}/ban', [AdminUsersController::class, 'ban'])->name('users.ban');
            Route::post('/users/{id}/unban', [AdminUsersController::class, 'unban'])->name('users.unban');
            Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');

            Route::resource('plans' , PlansController::class)->names('plans');
            Route::get('/plans/{planId}/schedule', [\App\Http\Controllers\Admin\PlanScheduleController::class, 'index'])->name('plans.schedule.index');
            Route::post('/plans/{planId}/schedule', [\App\Http\Controllers\Admin\PlanScheduleController::class, 'store'])->name('plans.schedule.store');
            Route::put('/plans/schedule/{id}', [\App\Http\Controllers\Admin\PlanScheduleController::class, 'update'])->name('plans.schedule.update');
            Route::delete('/plans/schedule/{id}', [\App\Http\Controllers\Admin\PlanScheduleController::class, 'destroy'])->name('plans.schedule.destroy');

            // Geo helpers
            Route::get('/countries/{country}/cities', [\App\Http\Controllers\Admin\GeoAdminController::class, 'cities'])
                ->name('countries.cities');

            Route::get('/content', [AdminContentController::class, 'index'])->name('content.index');
            Route::post('/content', [AdminContentController::class, 'update'])->name('content.update');
            Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
            Route::delete('/media/{id}', [AdminMediaController::class, 'destroy'])->name('media.destroy');

            Route::get('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionsController::class, 'index'])->name('subscriptions.index');
            Route::get('/payments', [\App\Http\Controllers\Admin\PaymentsAdminController::class, 'index'])->name('payments.index');
            Route::get('/reports', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('reports.index');
            Route::get('/reports/sales', [\App\Http\Controllers\Admin\ReportsController::class, 'sales'])->name('reports.sales');
            Route::get('/reports/payments', [\App\Http\Controllers\Admin\ReportsController::class, 'payments'])->name('reports.payments');
            Route::get('/reports/subscriptions', [\App\Http\Controllers\Admin\ReportsController::class, 'subscriptions'])->name('reports.subscriptions');
            Route::get('/reports/plan-sales', [\App\Http\Controllers\Admin\ReportsController::class, 'planSales'])->name('reports.plan-sales');
            Route::get('/reports/app-fees', [\App\Http\Controllers\Admin\ReportsController::class, 'appFees'])->name('reports.app-fees');
            Route::get('/reports/vat', [\App\Http\Controllers\Admin\ReportsController::class, 'vat'])->name('reports.vat');
            Route::get('/reports/completed-payouts', [AdvancedReportsController::class, 'completedPayouts'])->name('reports.completed-payouts');
            Route::get('/reports/active-courses', [AdvancedReportsController::class, 'activeCourses'])->name('reports.active-courses');
            Route::get('/reports/awaiting-offers', [AdvancedReportsController::class, 'awaitingOffers'])->name('reports.awaiting-offers');
            Route::get('/reports/rejected-progress', [AdvancedReportsController::class, 'rejectedProgress'])->name('reports.rejected-progress');
            Route::get('/reports/wallet-balances', [AdvancedReportsController::class, 'walletBalances'])->name('reports.wallet-balances');
            Route::get('/reports/points-balances', [AdvancedReportsController::class, 'pointsBalances'])->name('reports.points-balances');
            Route::get('/reports/reward-redemptions', [AdvancedReportsController::class, 'rewardRedemptions'])->name('reports.reward-redemptions');
            Route::get('/reports/wallet-payments', [AdvancedReportsController::class, 'walletPayments'])->name('reports.wallet-payments');
            Route::get('/geo', [\App\Http\Controllers\Admin\GeoAdminController::class, 'index'])->name('geo.index');
            Route::get('/geo/countries/create', [\App\Http\Controllers\Admin\GeoAdminController::class, 'createCountry'])->name('geo.countries.create');
            Route::get('/geo/countries/{id}/edit', [\App\Http\Controllers\Admin\GeoAdminController::class, 'editCountry'])->name('geo.countries.edit');
            Route::post('/geo/countries', [\App\Http\Controllers\Admin\GeoAdminController::class, 'storeCountry'])->name('geo.countries.store');
            Route::put('/geo/countries/{id}', [\App\Http\Controllers\Admin\GeoAdminController::class, 'updateCountry'])->name('geo.countries.update');
            Route::delete('/geo/countries/{id}', [\App\Http\Controllers\Admin\GeoAdminController::class, 'destroyCountry'])->name('geo.countries.destroy');
            Route::post('/geo/countries/{country}/cities', [\App\Http\Controllers\Admin\GeoAdminController::class, 'storeCities'])->name('geo.cities.store');
            Route::put('/geo/cities/{id}', [\App\Http\Controllers\Admin\GeoAdminController::class, 'updateCity'])->name('geo.cities.update');
            Route::delete('/geo/cities/{id}', [\App\Http\Controllers\Admin\GeoAdminController::class, 'destroyCity'])->name('geo.cities.destroy');
            Route::get('/ratings', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'index'])->name('ratings.index');
            Route::put('/ratings/{rating}', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'update'])->name('ratings.update');
            Route::delete('/ratings/{rating}', [\App\Http\Controllers\Admin\RatingsAdminController::class, 'destroy'])->name('ratings.destroy');
            Route::get('/wallets', [\App\Http\Controllers\Admin\WalletsController::class, 'index'])->name('wallets.index');
            Route::post('/wallets', [\App\Http\Controllers\Admin\WalletsController::class, 'store'])->name('wallets.store');
            Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'index'])->name('notifications.index');
            Route::get('/notifications/view', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'view'])->name('notifications.view');
            Route::get('/notifications/{id}', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'show'])->name('notifications.show');
            Route::post('/notifications/{id}/read', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'markAsRead'])->name('notifications.mark-read');
            Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'markAllRead'])->name('notifications.mark-all-read');
            Route::post('/notifications', [\App\Http\Controllers\Admin\NotificationsAdminController::class, 'send'])->name('notifications.send');

            // Cancellation Requests
            Route::get('/cancellation-requests', [\App\Http\Controllers\Admin\CancellationRequestsController::class, 'index'])->name('cancellation-requests.index');
            Route::get('/cancellation-requests/{id}', [\App\Http\Controllers\Admin\CancellationRequestsController::class, 'show'])->name('cancellation-requests.show');
            Route::post('/cancellation-requests/{id}/approve', [\App\Http\Controllers\Admin\CancellationRequestsController::class, 'approve'])->name('cancellation-requests.approve');
            Route::post('/cancellation-requests/{id}/reject', [\App\Http\Controllers\Admin\CancellationRequestsController::class, 'reject'])->name('cancellation-requests.reject');

            // Messages
            Route::get('/messages', [\App\Http\Controllers\Admin\MessagesController::class, 'index'])->name('messages.index');
            Route::get('/messages/{id}', [\App\Http\Controllers\Admin\MessagesController::class, 'show'])->name('messages.show');
            Route::get('/messages/all', [\App\Http\Controllers\Admin\MessagesController::class, 'messages'])->name('messages.messages');

            // Wallet Transactions
            Route::get('/wallet-transactions', [\App\Http\Controllers\Admin\WalletTransactionsController::class, 'index'])->name('wallet-transactions.index');
            Route::get('/wallet-transactions/{id}', [\App\Http\Controllers\Admin\WalletTransactionsController::class, 'show'])->name('wallet-transactions.show');
            Route::post('/wallet-transactions/{id}/approve', [\App\Http\Controllers\Admin\WalletTransactionsController::class, 'approve'])->name('wallet-transactions.approve');
            Route::post('/wallet-transactions/{id}/reject', [\App\Http\Controllers\Admin\WalletTransactionsController::class, 'reject'])->name('wallet-transactions.reject');

            // Support tickets
            Route::get('/support', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'index'])->name('support.index');
            Route::get('/support/{id}', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'show'])->name('support.show');
            Route::post('/support/{id}/reply', [\App\Http\Controllers\Admin\SupportTicketsController::class, 'reply'])->name('support.reply');

            // Prizes Management
            Route::resource('prizes', \App\Http\Controllers\Admin\PrizesController::class)->names('prizes');
            Route::get('/prize-redemptions', [\App\Http\Controllers\Admin\PrizeRedemptionsController::class, 'index'])->name('prize-redemptions.index');
            Route::get('/prize-redemptions/{id}', [\App\Http\Controllers\Admin\PrizeRedemptionsController::class, 'show'])->name('prize-redemptions.show');
            Route::post('/prize-redemptions/{id}/approve', [\App\Http\Controllers\Admin\PrizeRedemptionsController::class, 'approve'])->name('prize-redemptions.approve');
            Route::post('/prize-redemptions/{id}/reject', [\App\Http\Controllers\Admin\PrizeRedemptionsController::class, 'reject'])->name('prize-redemptions.reject');
            // Roles & permissions
            Route::get('/roles', [\App\Http\Controllers\Admin\RolesController::class, 'index'])->name('roles.index');
            Route::post('/roles', [\App\Http\Controllers\Admin\RolesController::class, 'store'])->name('roles.store');
            Route::put('/roles/{id}', [\App\Http\Controllers\Admin\RolesController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{id}', [\App\Http\Controllers\Admin\RolesController::class, 'destroy'])->name('roles.destroy');
            Route::get('/permissions', [\App\Http\Controllers\Admin\PermissionsController::class, 'index'])->name('permissions.index');
            Route::post('/permissions', [\App\Http\Controllers\Admin\PermissionsController::class, 'store'])->name('permissions.store');
            Route::delete('/permissions/{id}', [\App\Http\Controllers\Admin\PermissionsController::class, 'destroy'])->name('permissions.destroy');
            Route::get('/users/{id}/roles', [\App\Http\Controllers\Admin\UserRolesController::class, 'edit'])->name('users.roles.edit');
            Route::put('/users/{id}/roles', [\App\Http\Controllers\Admin\UserRolesController::class, 'update'])->name('users.roles.update');

            // Settings
            Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/how-it-works', [\App\Http\Controllers\Admin\HowItWorksController::class, 'update'])->name('settings.howitworks.update');
        });
    });

// Allow exiting impersonation even بعد تسجيل الدخول كـ مستخدم
Route::match(['get', 'post'], '/admin/impersonation/stop', [ImpersonationController::class, 'stop'])
    ->middleware('auth')
    ->name('admin.impersonation.stop');

if (app()->environment('local')) {
    Route::get('/admin/dev-login', function () {
        $admin = User::role('ADMIN')->first();
        if (!$admin) {
            abort(404, 'No admin user');
        }
        Auth::login($admin);
        return redirect()->route('admin.dashboard');
    });
}
