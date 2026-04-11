<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;

class DashboardController extends BaseController
{
    public function __invoke(\Illuminate\Http\Request $request)
    {
        $range = $request->query('range', 'day');
        $now = Carbon::now();
        $from = match ($range) {
            'month' => $now->copy()->startOfMonth(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfDay(),
        };

        $planCount = \App\Models\Plan::count();
        $countriesCount = \App\Models\Country::count();
        $usersCount = \App\Models\User::count();
        $trainersCount = \App\Models\User::role('TRAINER')->count();
        $bookingsCount = \App\Models\UserRequest::count();
        $activeBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_IN_TRAINING)->count();
        $completedBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_COMPLETED)->count();
        $cancelledBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_CANCELLED)->count();
        $awaitingOffers = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_AWAITING_OFFERS)->count();
        $pendingCancellations = \App\Models\CancellationRequest::where('status', 'pending')->count();
        $pendingWalletRequests = \App\Models\WalletTransaction::where('status', 'pending')
            ->where('type', \App\Models\WalletTransaction::TYPE_TOPUP_REQUEST)
            ->count();
        $pendingWithdrawalRequests = \App\Models\WalletTransaction::where('status', 'pending')
            ->where('type', \App\Models\WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->count();
        $pendingPrizeRequests = \App\Models\RewardRedemption::where('status', 'pending')->count();
        $pendingSupportTickets = \App\Models\SupportTicket::where('status', 'open')->count();
        $unreadNotifications = auth()->user()->unreadNotifications()->count();

        $succeededPayments = \App\Models\Payment::where('status', \App\Models\Payment::STATUS_SUCCEEDED)
            ->whereBetween('created_at', [$from, $now]);
        $salesMinor = (int) $succeededPayments->clone()->sum('amount_minor');
        $reservationFeesMinor = (int) $succeededPayments->clone()
            ->where('type', \App\Models\Payment::TYPE_RESERVATION_FEE)
            ->sum('amount_minor');
        $packageFeesMinor = (int) $succeededPayments->clone()
            ->whereIn('type', [
                \App\Models\Payment::TYPE_PLAN_PARTIAL,
                \App\Models\Payment::TYPE_PLAN_FULL,
            ])
            ->sum('amount_minor');

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
            'planCount','countriesCount','usersCount','trainersCount',
            'bookingsCount','activeBookings','completedBookings',
            'cancelledBookings',
            'pendingCancellations','pendingWalletRequests','pendingWithdrawalRequests','pendingPrizeRequests',
            'pendingSupportTickets','unreadNotifications',
            'labels','userSeries','planSeries','bookingSeries',
            'range','salesMinor','reservationFeesMinor','packageFeesMinor','awaitingOffers'
        ));
    }

    public function courseDetails()
    {
        $plans = \App\Models\Plan::with(['country'])->active()->orderBy('title')->get();
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
