<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;

class DashboardController extends BaseController
{
    public function __invoke()
    {
        $planCount = \App\Models\Plan::count();
        $countriesCount = \App\Models\Country::count();
        $citiesCount = \App\Models\City::count();
        $usersCount = \App\Models\User::count();
        $trainersCount = \App\Models\User::role('TRAINER')->count();
        $bookingsCount = \App\Models\UserRequest::count();
        $pendingBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_PENDING_PAYMENT)->count();
        $activeBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_IN_TRAINING)->count();
        $completedBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_COMPLETED)->count();
        $pendingCancellations = \App\Models\CancellationRequest::where('status', 'pending')->count();
        $pendingWalletRequests = \App\Models\WalletTransaction::where('status', 'pending')->count();
        $pendingPrizeRequests = \App\Models\RewardRedemption::where('status', 'pending')->count();
        $pendingSupportTickets = \App\Models\SupportTicket::where('status', 'open')->count();
        $unreadNotifications = auth()->user()->unreadNotifications()->count();

        $labels = [];
        $userSeries = [];
        $planSeries = [];
        $bookingSeries = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('Y-m-d');
            $userSeries[] = \App\Models\User::whereDate('created_at', $day)->count();
            $planSeries[] = \App\Models\Plan::whereDate('created_at', $day)->count();
            $bookingSeries[] = \App\Models\UserRequest::whereDate('created_at', $day)->count();
        }

        return view('admin.dashboard', compact(
            'planCount','countriesCount','citiesCount','usersCount','trainersCount',
            'bookingsCount','pendingBookings','activeBookings','completedBookings',
            'pendingCancellations','pendingWalletRequests','pendingPrizeRequests',
            'pendingSupportTickets','unreadNotifications',
            'labels','userSeries','planSeries','bookingSeries'
        ));
    }

    public function courseDetails()
    {
        $plans = \App\Models\Plan::with(['country', 'city'])->active()->orderBy('title')->get();
        $recentBookings = \App\Models\UserRequest::with(['user', 'plan'])
            ->latest()
            ->limit(10)
            ->get();
        
        $bookingStats = [
            'total' => \App\Models\UserRequest::count(),
            'pending' => \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_PENDING_PAYMENT)->count(),
            'in_training' => \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_IN_TRAINING)->count(),
            'completed' => \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_COMPLETED)->count(),
        ];

        return view('admin.course-details', compact('plans', 'recentBookings', 'bookingStats'));
    }

}
