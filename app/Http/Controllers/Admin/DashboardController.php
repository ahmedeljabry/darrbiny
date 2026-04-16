<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\AppExpense;
use App\Services\Admin\AppWalletAccountService;
use App\Services\Admin\ReportsService;
use App\Support\ReportCurrencyConverter;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;

class DashboardController extends BaseController
{
    public function __construct(
        private readonly AppWalletAccountService $appWalletAccountService,
        private readonly ReportsService $reportsService,
        private readonly ReportCurrencyConverter $reportCurrencyConverter
    ) {}

    public function __invoke(Request $request)
    {
        $now = Carbon::now();
        [$range, $from, $to, $rangeLabel, $usesCustomRange] = $this->resolveDateRange($request, $now);

        $planCount = \App\Models\Plan::whereBetween('created_at', [$from, $to])->count();
        $countriesCount = \App\Models\Country::whereBetween('created_at', [$from, $to])->count();
        $usersCount = \App\Models\User::whereBetween('created_at', [$from, $to])->count();
        $trainersCount = \App\Models\User::role('TRAINER')
            ->whereBetween('users.created_at', [$from, $to])
            ->count();
        $bookingsCount = \App\Models\UserRequest::whereBetween('created_at', [$from, $to])->count();
        $activeBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_IN_TRAINING)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $completedBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_COMPLETED)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $cancelledBookings = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_CANCELLED)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $awaitingOffers = \App\Models\UserRequest::where('status', \App\Models\UserRequest::STATUS_AWAITING_OFFERS)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $pendingCancellations = \App\Models\CancellationRequest::where('status', 'pending')
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $pendingWalletRequests = \App\Models\WalletTransaction::where('status', 'pending')
            ->where('type', \App\Models\WalletTransaction::TYPE_TOPUP_REQUEST)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $pendingWithdrawalRequests = \App\Models\WalletTransaction::where('status', 'pending')
            ->where('type', \App\Models\WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $pendingPrizeRequests = \App\Models\RewardRedemption::where('status', 'pending')
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $pendingSupportTickets = \App\Models\SupportTicket::where('status', 'open')
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $unreadNotifications = auth()->user()->unreadNotifications()->count();
        $dashboardAlerts = auth()->user()
            ->unreadNotifications()
            ->latest()
            ->limit(20)
            ->get()
            ->filter(fn ($notification) => in_array($notification->data['type'] ?? '', [
                'wallet_withdraw_request',
                'prize_request',
                'support_ticket_user_reply',
            ], true))
            ->take(5)
            ->values();

        $dashboardIncomeFilters = [
            'from' => $from,
            'to' => $to,
            'direction' => 'in',
        ];
        $packageReservationFeesMinor = $this->appWalletAccountService->summary([
            ...$dashboardIncomeFilters,
            'source' => \App\Models\Payment::TYPE_PLAN_PARTIAL,
        ])['incoming_minor'];
        $appFeesMinor = $this->appWalletAccountService->summary([
            ...$dashboardIncomeFilters,
            'source' => 'app_fee',
        ])['incoming_minor'];
        $packageReservationRefundsMinor = $this->reportsService->allocatedCancellationRefundsMinor(
            $from,
            $to,
            [],
            'plan_partial'
        );
        $appFeesRefundsMinor = $this->reportsService->allocatedCancellationRefundsMinor(
            $from,
            $to,
            [],
            'app_fee'
        );
        $packageReservationFeesMinor -= $packageReservationRefundsMinor;
        $appFeesMinor -= $appFeesRefundsMinor;
        $salesMinor = $packageReservationFeesMinor + $appFeesMinor;
        $succeededPayments = \App\Models\Payment::where('status', \App\Models\Payment::STATUS_SUCCEEDED)
            ->whereBetween('created_at', [$from, $to]);
        $bookingsValueMinor = $this->reportCurrencyConverter->convertGroupedMinorSumsToReportCurrency(
            $succeededPayments->clone()->where('type', \App\Models\Payment::TYPE_PLAN_FULL),
            'amount_minor'
        );
        $bookingsValueMinor -= $this->reportsService->allocatedCancellationRefundsMinor(
            $from,
            $to,
            [],
            'plan_full'
        );
        $expensesMinor = (int) AppExpense::query()
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount_minor');
        $netProfitMinor = $salesMinor - $expensesMinor;
        $appWalletBalanceMinor = $this->appWalletAccountService->summary()['net_minor'];

        [
            'labels' => $labels,
            'userSeries' => $userSeries,
            'planSeries' => $planSeries,
            'bookingSeries' => $bookingSeries,
            'trendLabel' => $trendLabel,
        ] = $this->buildTrendSeries($from, $to);

        return view('admin.dashboard', compact(
            'planCount','countriesCount','usersCount','trainersCount',
            'bookingsCount','activeBookings','completedBookings',
            'cancelledBookings',
            'pendingCancellations','pendingWalletRequests','pendingWithdrawalRequests','pendingPrizeRequests',
            'pendingSupportTickets','unreadNotifications',
            'dashboardAlerts',
            'labels','userSeries','planSeries','bookingSeries',
            'range','salesMinor','packageReservationFeesMinor','appFeesMinor','awaitingOffers',
            'from','to','rangeLabel','usesCustomRange','trendLabel',
            'bookingsValueMinor','expensesMinor','netProfitMinor','appWalletBalanceMinor'
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

    /**
     * @return array{0:string, 1:Carbon, 2:Carbon, 3:string, 4:bool}
     */
    private function resolveDateRange(Request $request, Carbon $now): array
    {
        $range = (string) $request->query('range', 'day');
        if (! in_array($range, ['day', 'month', 'year'], true)) {
            $range = 'day';
        }

        $customFrom = $this->parseDate($request->query('from'));
        $customTo = $this->parseDate($request->query('to'), true);

        if ($customFrom || $customTo) {
            $from = $customFrom?->copy() ?? $customTo?->copy()->startOfDay() ?? $now->copy()->startOfDay();
            $to = $customTo?->copy() ?? $now->copy()->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return [
                $range,
                $from,
                $to,
                'من ' . $from->translatedFormat('d M Y') . ' إلى ' . $to->translatedFormat('d M Y'),
                true,
            ];
        }

        $from = match ($range) {
            'month' => $now->copy()->startOfMonth(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfDay(),
        };

        $label = match ($range) {
            'month' => 'هذا الشهر',
            'year' => 'هذا العام',
            default => 'اليوم',
        };

        return [$range, $from, $now->copy()->endOfDay(), $label, false];
    }

    private function parseDate(null|string|int $value, bool $endOfDay = false): ?Carbon
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     userSeries: array<int, int>,
     *     planSeries: array<int, int>,
     *     bookingSeries: array<int, int>,
     *     trendLabel: string
     * }
     */
    private function buildTrendSeries(Carbon $from, Carbon $to): array
    {
        $labels = [];
        $userSeries = [];
        $planSeries = [];
        $bookingSeries = [];

        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();
        $useDailyGranularity = $start->diffInDays($end) <= 31;
        $period = $useDailyGranularity
            ? CarbonPeriod::create($start, '1 day', $end->copy()->startOfDay())
            : CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->startOfMonth());

        foreach ($period as $point) {
            $bucketStart = $useDailyGranularity ? $point->copy()->startOfDay() : $point->copy()->startOfMonth();
            $bucketEnd = $useDailyGranularity ? $point->copy()->endOfDay() : $point->copy()->endOfMonth();

            if ($bucketStart->lt($start)) {
                $bucketStart = $start->copy();
            }

            if ($bucketEnd->gt($end)) {
                $bucketEnd = $end->copy();
            }

            $labels[] = $useDailyGranularity
                ? $point->format('Y-m-d')
                : $point->translatedFormat('M Y');
            $userSeries[] = \App\Models\User::whereBetween('created_at', [$bucketStart, $bucketEnd])->count();
            $planSeries[] = \App\Models\Plan::whereBetween('created_at', [$bucketStart, $bucketEnd])->count();
            $bookingSeries[] = \App\Models\UserRequest::whereBetween('created_at', [$bucketStart, $bucketEnd])->count();
        }

        return [
            'labels' => $labels,
            'userSeries' => $userSeries,
            'planSeries' => $planSeries,
            'bookingSeries' => $bookingSeries,
            'trendLabel' => $useDailyGranularity ? 'يومي' : 'شهري',
        ];
    }

}
