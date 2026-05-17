<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ActiveCoursesExport;
use App\Exports\ArrayRowsExport;
use App\Exports\CompletedPayoutsExport;
use App\Exports\PointsBalancesExport;
use App\Exports\PrizeRedemptionsExport;
use App\Exports\WalletBalancesExport;
use App\Exports\WalletPaymentsExport;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\UserScheduleProgress;
use App\Models\WalletTransaction;
use App\Support\ReportCurrencyConverter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvancedReportsController extends BaseController
{
    private function maybeExportCsv(Request $request, string $filename, array $headers, iterable $rows): ?StreamedResponse
    {
        if ($request->query('export') !== 'csv') {
            return null;
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function view(
        string $title,
        array $headers,
        array $rows,
        Request $request,
        string $filename,
        bool $supportsExcel = false,
        array $filters = [],
        array $stats = []
    ) {
        if ($export = $this->maybeExportCsv($request, $filename, $headers, $rows)) {
            return $export;
        }

        return view('admin.reports.custom', compact(
            'title',
            'headers',
            'rows',
            'supportsExcel',
            'filters',
            'stats'
        ));
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if (blank($dateFrom) && blank($dateTo)) {
            return [null, null];
        }

        return [
            filled($dateFrom) ? (string) $dateFrom : null,
            filled($dateTo) ? (string) $dateTo : null,
        ];
    }

    private function applyDateRange(Builder $query, ?string $dateFrom, ?string $dateTo, string $column = 'created_at'): void
    {
        if ($dateFrom) {
            $query->whereDate($column, '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate($column, '<=', $dateTo);
        }
    }

    private function applyNamePhoneFilters(Builder $query, array $relations, string $name, string $phone): void
    {
        if ($name === '' && $phone === '') {
            return;
        }

        $query->where(function (Builder $nestedQuery) use ($relations, $name, $phone): void {
            foreach ($relations as $index => $relation) {
                $method = $index === 0 ? 'whereHas' : 'orWhereHas';

                $nestedQuery->{$method}($relation, function (Builder $personQuery) use ($name, $phone): void {
                    $personQuery->where(function (Builder $matchQuery) use ($name, $phone): void {
                        if ($name !== '') {
                            $matchQuery->where('name', 'like', '%'.$name.'%');
                        }

                        if ($phone !== '') {
                            $method = $name === '' ? 'where' : 'orWhere';
                            $matchQuery->{$method}('phone_with_cc', 'like', '%'.$phone.'%');
                        }
                    });
                });
            }
        });
    }

    private function buildDailyFilters(Request $request, ?string $dateFrom, ?string $dateTo, array $extra = []): array
    {
        return [
            'name' => trim((string) $request->query('name', '')),
            'phone' => trim((string) $request->query('phone', '')),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ] + $extra;
    }

    public function completedPayouts(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $name = trim((string) $request->query('name', ''));
        $phone = trim((string) $request->query('phone', ''));

        $paymentsQuery = Payment::with([
            'userRequest.country',
            'userRequest.plan.country',
            'userRequest.payout',
            'userRequest.trainer.country',
            'userRequest.trainer.trainerProfile.country',
        ])
            ->where('type', Payment::TYPE_PLAN_FULL)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->whereHas('userRequest', fn (Builder $query) => $query->where('status', UserRequest::STATUS_COMPLETED));

        $this->applyDateRange($paymentsQuery, $dateFrom, $dateTo);
        $this->applyNamePhoneFilters($paymentsQuery, ['userRequest.trainer'], $name, $phone);

        $payments = $this->attachTrainerWithdrawalExecution(
            $paymentsQuery->orderByDesc('created_at')->get()
        );

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new CompletedPayoutsExport($payments),
                'completed-payouts-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $rows = $payments->map(function (Payment $p) {
            $trainer = $p->userRequest?->trainer;
            $payoutStatus = $p->userRequest?->payout?->status;
            $payoutStatusLabel = match ($payoutStatus) {
                Payout::STATUS_SENT => 'منفذة',
                Payout::STATUS_APPROVED => 'معتمدة',
                Payout::STATUS_FAILED => 'فشلت',
                Payout::STATUS_PENDING_REVIEW => 'غير منفذة',
                default => 'غير منفذة',
            };
            $executionStatus = (string) ($p->payout_execution_status ?? $payoutStatusLabel);

            return [
                $trainer?->name ?? '-',
                $trainer?->phone_with_cc ?? '-',
                $trainer?->country?->name
                    ?? $trainer?->trainerProfile?->country?->name
                    ?? $p->userRequest?->country?->name
                    ?? $p->userRequest?->plan?->country?->name
                    ?? '-',
                $trainer?->bank_name ?? '-',
                $trainer?->bank_account ?? '-',
                $trainer?->iban ?? '-',
                number_format($p->trainer_net_minor / 100, 2),
                number_format(((int) ($p->executed_withdrawal_minor ?? 0)) / 100, 2),
                number_format(((int) ($p->remaining_trainer_net_minor ?? $p->trainer_net_minor)) / 100, 2),
                $executionStatus,
                $p->created_at?->format('Y-m-d H:i') ?? '-',
            ];
        })->all();

        $totalPayoutsMinor = (int) $payments->sum('trainer_net_minor');
        $executedPayoutsMinor = (int) $payments->sum(fn (Payment $payment): int => (int) ($payment->executed_withdrawal_minor ?? 0));
        $remainingPayoutsMinor = (int) $payments->sum(fn (Payment $payment): int => (int) ($payment->remaining_trainer_net_minor ?? $payment->trainer_net_minor));
        $filters = $this->buildDailyFilters($request, $dateFrom, $dateTo);

        return $this->view(
            'الكورسات المكتملة ومستحقات المدرب',
            ['المدرب', 'الجوال', 'الدولة', 'البنك', 'رقم الحساب', 'IBAN', 'صافي المدرب', 'تم سحبه', 'المتبقي', 'حالة السحب', 'تاريخ الدفعة'],
            $rows,
            $request,
            'completed-payouts',
            true,
            $filters,
            [
                ['label' => 'عدد النتائج', 'value' => number_format(count($rows)), 'icon' => 'list-details'],
                ['label' => 'إجمالي مستحقات المدربين', 'value' => number_format($totalPayoutsMinor / 100, 2), 'icon' => 'wallet', 'tone' => 'success'],
                ['label' => 'تم سحبه', 'value' => number_format($executedPayoutsMinor / 100, 2), 'icon' => 'circle-check', 'tone' => 'primary'],
                ['label' => 'المتبقي غير المنفذ', 'value' => number_format($remainingPayoutsMinor / 100, 2), 'icon' => 'cash-off', 'tone' => 'warning'],
                ['label' => 'الفلاتر النشطة', 'value' => number_format(collect($filters)->filter(fn ($value) => filled($value))->count()), 'icon' => 'adjustments-horizontal', 'tone' => 'secondary'],
            ]
        );
    }

    private function attachTrainerWithdrawalExecution(\Illuminate\Support\Collection $payments): \Illuminate\Support\Collection
    {
        $payments->each(function (Payment $payment): void {
            $netMinor = max(0, (int) $payment->trainer_net_minor);
            $hasSentPayout = $payment->userRequest?->payout?->status === Payout::STATUS_SENT;

            $payment->setAttribute('executed_withdrawal_minor', $hasSentPayout ? $netMinor : 0);
            $payment->setAttribute('remaining_trainer_net_minor', $hasSentPayout ? 0 : $netMinor);
        });

        $trainerIds = $payments
            ->map(fn (Payment $payment): ?string => $payment->userRequest?->trainer_id ?? $payment->userRequest?->trainer?->id)
            ->filter()
            ->unique()
            ->values();

        if ($trainerIds->isEmpty()) {
            return $payments;
        }

        $withdrawalsByTrainer = WalletTransaction::query()
            ->whereIn('user_id', $trainerIds)
            ->where('type', WalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->where('status', WalletTransaction::STATUS_APPROVED)
            ->orderByRaw('COALESCE(processed_at, updated_at, created_at) asc')
            ->orderBy('created_at')
            ->get(['user_id', 'amount', 'processed_at', 'updated_at', 'created_at'])
            ->groupBy('user_id');

        $paymentsByTrainer = $payments
            ->filter(fn (Payment $payment): bool => filled($payment->userRequest?->trainer_id ?? $payment->userRequest?->trainer?->id))
            ->groupBy(fn (Payment $payment): string => (string) ($payment->userRequest?->trainer_id ?? $payment->userRequest?->trainer?->id));

        $paymentsByTrainer->each(function (\Illuminate\Support\Collection $trainerPayments, string $trainerId) use ($withdrawalsByTrainer): void {
            foreach ($withdrawalsByTrainer->get($trainerId, collect()) as $withdrawal) {
                $withdrawalRemainingMinor = max(0, (int) $withdrawal->amount);
                $withdrawalAt = $withdrawal->processed_at ?? $withdrawal->updated_at ?? $withdrawal->created_at;

                while ($withdrawalRemainingMinor > 0) {
                    $eligiblePayment = $trainerPayments
                        ->filter(function (Payment $payment) use ($withdrawalAt): bool {
                            $remainingMinor = (int) $payment->getAttribute('remaining_trainer_net_minor');

                            if ($remainingMinor <= 0) {
                                return false;
                            }

                            if ($withdrawalAt === null || $payment->created_at === null) {
                                return true;
                            }

                            return $payment->created_at->lessThanOrEqualTo($withdrawalAt);
                        })
                        ->sortByDesc(fn (Payment $payment): int => $payment->created_at?->getTimestamp() ?? 0)
                        ->first();

                    if (! $eligiblePayment instanceof Payment) {
                        break;
                    }

                    $paymentRemainingMinor = (int) $eligiblePayment->getAttribute('remaining_trainer_net_minor');
                    $executedMinor = min($paymentRemainingMinor, $withdrawalRemainingMinor);

                    $eligiblePayment->setAttribute(
                        'executed_withdrawal_minor',
                        (int) $eligiblePayment->getAttribute('executed_withdrawal_minor') + $executedMinor
                    );
                    $eligiblePayment->setAttribute('remaining_trainer_net_minor', $paymentRemainingMinor - $executedMinor);

                    $withdrawalRemainingMinor -= $executedMinor;
                }
            }
        });

        $payments->each(function (Payment $payment): void {
            $netMinor = max(0, (int) $payment->trainer_net_minor);
            $executedMinor = (int) $payment->getAttribute('executed_withdrawal_minor');
            $remainingMinor = (int) $payment->getAttribute('remaining_trainer_net_minor');

            $payment->setAttribute('payout_execution_status', match (true) {
                $netMinor <= 0 || $remainingMinor <= 0 => 'منفذة',
                $executedMinor > 0 => 'منفذة جزئياً',
                default => 'غير منفذة',
            });
        });

        return $payments;
    }

    public function activeCourses(Request $request)
    {
        $courses = UserRequest::with(['trainer', 'user', 'payments' => function ($q) {
            $q->where('status', Payment::STATUS_SUCCEEDED)->orderByDesc('created_at');
        }])->where('status', UserRequest::STATUS_IN_TRAINING)->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new ActiveCoursesExport($courses),
                'active-courses-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $rows = $courses->map(function (UserRequest $req) {
            $payment = $req->payments->first();

            return [
                $req->id,
                $req->trainer?->name ?? '-',
                $req->user?->name ?? '-',
                $payment ? number_format($payment->amount_minor / 100, 2) : '-',
                $req->start_date?->toDateString(),
            ];
        })->all();

        return $this->view(
            'الكورسات النشطة',
            ['الطلب', 'المدرب', 'المستخدم', 'قيمة الكورس', 'تاريخ البدء'],
            $rows,
            $request,
            'active-courses',
            true
        );
    }

    public function awaitingOffers(Request $request)
    {
        $requests = UserRequest::with('user')
            ->where('status', UserRequest::STATUS_AWAITING_OFFERS)
            ->get();

        $rows = $requests->map(fn ($r) => [
            $r->id,
            $r->user?->name ?? '-',
            $r->user?->phone_with_cc ?? '-',
            $r->created_at,
        ])->all();

        return $this->view(
            'طلبات بانتظار عروض الأسعار',
            ['الطلب', 'المستخدم', 'الجوال', 'تاريخ الإنشاء'],
            $rows,
            $request,
            'awaiting-offers'
        );
    }

    public function rejectedProgress(Request $request)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $name = trim((string) $request->query('name', ''));
        $phone = trim((string) $request->query('phone', ''));

        $itemsQuery = UserScheduleProgress::with(['userRequest.user', 'userRequest.trainer'])
            ->where('status', UserScheduleProgress::STATUS_REJECTED);

        $this->applyDateRange($itemsQuery, $dateFrom, $dateTo);
        $this->applyNamePhoneFilters($itemsQuery, ['userRequest.user', 'userRequest.trainer'], $name, $phone);

        $items = $itemsQuery->orderByDesc('created_at')->get();

        $rows = $items->map(function (UserScheduleProgress $p) {
            return [
                $p->userRequest?->formatted_order_number ? ('#'.$p->userRequest->formatted_order_number) : '-',
                $p->userRequest?->user?->name ?? '-',
                $p->userRequest?->user?->phone_with_cc ?? '-',
                $p->userRequest?->trainer?->name ?? '-',
                $p->userRequest?->trainer?->phone_with_cc ?? '-',
                $p->day_number,
                $p->rejection_reason ?: '-',
                $p->created_at?->format('Y-m-d H:i') ?? '-',
            ];
        })->all();

        $headers = ['الطلب', 'الطالبة', 'جوال الطالبة', 'المدرب', 'جوال المدرب', 'اليوم', 'سبب الرفض', 'التاريخ'];

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new ArrayRowsExport($headers, $rows),
                'rejected-progress-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        return $this->view(
            'الكورسات مع رفض في الإنجاز اليومي',
            $headers,
            $rows,
            $request,
            'rejected-progress',
            true,
            $this->buildDailyFilters($request, $dateFrom, $dateTo)
        );
    }

    public function walletBalances(Request $request)
    {
        $dateFrom = filled($request->query('date_from')) ? (string) $request->query('date_from') : null;
        $dateTo = filled($request->query('date_to')) ? (string) $request->query('date_to') : null;
        $name = trim((string) $request->query('name', ''));
        $phone = trim((string) $request->query('phone', ''));
        $countryId = filled($request->query('country_id')) ? (string) $request->query('country_id') : null;

        $usersQuery = User::query()
            ->with(['country', 'bankCountry'])
            ->where('points_balance', '>', 0);

        $this->applyDateRange($usersQuery, $dateFrom, $dateTo);

        if ($name !== '' || $phone !== '') {
            $usersQuery->where(function (Builder $query) use ($name, $phone): void {
                if ($name !== '') {
                    $query->where('name', 'like', '%'.$name.'%');
                }

                if ($phone !== '') {
                    $method = $name === '' ? 'where' : 'orWhere';
                    $query->{$method}('phone_with_cc', 'like', '%'.$phone.'%');
                }
            });
        }

        if ($countryId !== null) {
            $usersQuery->where(function (Builder $query) use ($countryId): void {
                $query->where('country_id', $countryId)
                    ->orWhere('bank_country_id', $countryId);
            });
        }

        $users = $usersQuery->orderByDesc('points_balance')->orderBy('name')->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new WalletBalancesExport($users),
                'wallet-balances-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $rows = $users->map(fn (User $u) => [
            $u->id,
            $u->name ?? '-',
            $u->phone_with_cc ?? '-',
            $u->country?->name ?? $u->bankCountry?->name ?? '-',
            $u->points_balance,
        ])->all();

        $countries = Country::query()->orderBy('name')->get(['id', 'name']);
        $filters = $this->buildDailyFilters($request, $dateFrom, $dateTo, [
            'country_id' => $countryId,
            'country_options' => $countries->pluck('name', 'id')->all(),
        ]);

        return $this->view(
            'المحافظ التي تحتوي مبالغ',
            ['المعرف', 'الاسم', 'الجوال', 'الدولة', 'الرصيد'],
            $rows,
            $request,
            'wallet-balances',
            true,
            $filters,
            [
                ['label' => 'إجمالي قيمة المحفظة', 'value' => number_format((float) $users->sum('points_balance'), 2), 'icon' => 'wallet', 'tone' => 'success'],
                ['label' => 'عدد الحركات', 'value' => number_format($users->count()), 'icon' => 'list-details', 'tone' => 'primary'],
                ['label' => 'عدد الأعمدة', 'value' => number_format(5), 'icon' => 'table', 'tone' => 'info'],
                ['label' => 'الفلاتر النشطة', 'value' => number_format(collect($filters)->except('country_options')->filter(fn ($value) => filled($value))->count()), 'icon' => 'adjustments-horizontal', 'tone' => 'warning'],
            ]
        );
    }

    public function pointsBalances(Request $request)
    {
        $dateFrom = filled($request->query('date_from')) ? (string) $request->query('date_from') : null;
        $dateTo = filled($request->query('date_to')) ? (string) $request->query('date_to') : null;
        $name = trim((string) $request->query('name', ''));
        $phone = trim((string) $request->query('phone', ''));

        $usersQuery = User::query()
            ->with('roles')
            ->select('users.*')
            ->selectSub(function ($query): void {
                $query->from('users as referred_users')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('referred_users.referred_by', 'users.id')
                    ->whereExists(function ($paymentQuery): void {
                        $paymentQuery->selectRaw('1')
                            ->from('payments')
                            ->whereColumn('payments.user_id', 'referred_users.id')
                            ->where('payments.type', Payment::TYPE_PLAN_FULL)
                            ->where('payments.status', Payment::STATUS_SUCCEEDED);
                    });
            }, 'referral_points_earned')
            ->when($name !== '' || $phone !== '', function (Builder $query) use ($name, $phone): void {
                $query->where(function (Builder $nested) use ($name, $phone): void {
                    if ($name !== '') {
                        $nested->where('name', 'like', '%'.$name.'%');
                    }

                    if ($phone !== '') {
                        $method = $name === '' ? 'where' : 'orWhere';
                        $nested->{$method}('phone_with_cc', 'like', '%'.$phone.'%');
                    }
                });
            });

        $this->applyDateRange($usersQuery, $dateFrom, $dateTo);

        $users = $usersQuery
            ->orderByDesc('referral_points_earned')
            ->orderBy('name')
            ->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new PointsBalancesExport($users),
                'points-balances-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $rows = $users->map(fn (User $u) => [
            $u->id,
            $u->name ?? '-',
            $u->phone_with_cc ?? '-',
            $u->hasRole('TRAINER') ? 'مدرب' : 'مستخدم',
            (int) ($u->referral_points_earned ?? 0),
        ])->all();

        return $this->view(
            'تقرير نقاط الإحالة لكل مستخدم/مدرب',
            ['المعرف', 'الاسم', 'الجوال', 'النوع', 'نقاط الإحالة'],
            $rows,
            $request,
            'points-balances',
            true,
            $this->buildDailyFilters($request, $dateFrom, $dateTo)
        );
    }

    public function rewardRedemptions(Request $request)
    {
        $dateFrom = filled($request->query('date_from')) ? (string) $request->query('date_from') : null;
        $dateTo = filled($request->query('date_to')) ? (string) $request->query('date_to') : null;
        $name = trim((string) $request->query('name', ''));
        $phone = trim((string) $request->query('phone', ''));

        $itemsQuery = RewardRedemption::with(['user', 'reward']);

        $this->applyDateRange($itemsQuery, $dateFrom, $dateTo);
        $this->applyNamePhoneFilters($itemsQuery, ['user'], $name, $phone);

        $items = $itemsQuery->latest()->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new PrizeRedemptionsExport($items),
                'reward-redemptions-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $rows = $items->map(fn (RewardRedemption $r) => [
            $r->id,
            $r->user?->name ?? '-',
            $r->user?->phone_with_cc ?? '-',
            $r->reward?->title ?? '-',
            $r->points_spent,
            $r->status,
            $r->created_at,
        ])->all();

        return $this->view(
            'طلبات استبدال النقاط بالمكافآت',
            ['المعرف', 'المستخدم/المدرب', 'الجوال', 'المكافأة', 'النقاط', 'الحالة', 'التاريخ'],
            $rows,
            $request,
            'reward-redemptions',
            true,
            $this->buildDailyFilters($request, $dateFrom, $dateTo)
        );
    }

    public function appProfits(Request $request)
    {
        $dateFrom = filled($request->query('date_from')) ? (string) $request->query('date_from') : null;
        $dateTo = filled($request->query('date_to')) ? (string) $request->query('date_to') : null;
        $name = trim((string) $request->query('name', ''));
        $phone = trim((string) $request->query('phone', ''));
        $countryId = filled($request->query('country_id')) ? (string) $request->query('country_id') : null;
        $status = filled($request->query('status')) ? (string) $request->query('status') : null;
        $statusOptions = [
            UserRequest::STATUS_AWAITING_OFFERS => 'انتظار العروض',
            UserRequest::STATUS_CANCELLED => 'ملغي',
            UserRequest::STATUS_IN_TRAINING => 'قيد التدريب',
            UserRequest::STATUS_COMPLETED => 'مكتمل',
        ];
        $status = array_key_exists((string) $status, $statusOptions) ? $status : null;

        $requestsQuery = UserRequest::query()
            ->with([
                'user',
                'country',
                'plan.country',
                'payments' => fn ($query) => $query->where('status', Payment::STATUS_SUCCEEDED),
            ])
            ->whereHas('payments', fn (Builder $query) => $query->where('status', Payment::STATUS_SUCCEEDED));

        $this->applyDateRange($requestsQuery, $dateFrom, $dateTo);
        $this->applyNamePhoneFilters($requestsQuery, ['user'], $name, $phone);

        if ($countryId !== null) {
            $requestsQuery->where(function (Builder $query) use ($countryId): void {
                $query->where('country_id', $countryId)
                    ->orWhereHas('plan', fn (Builder $planQuery) => $planQuery->where('country_id', $countryId));
            });
        }

        if ($status !== null) {
            $requestsQuery->where('status', $status);
        }

        $converter = app(ReportCurrencyConverter::class);
        $requests = $requestsQuery->latest()->get();
        $totalBookingFeesMinor = 0;
        $totalPackageFeesMinor = 0;

        $rows = $requests->map(function (UserRequest $userRequest) use ($converter, $statusOptions, &$totalBookingFeesMinor, &$totalPackageFeesMinor): array {
            $bookingFeesMinor = (int) $userRequest->payments
                ->filter(fn (Payment $payment) => in_array($payment->type, Payment::partialTypes(), true))
                ->sum(fn (Payment $payment) => $converter->convertMinor((int) $payment->amount_minor, $payment->currency));

            $packageFeesMinor = (int) $userRequest->payments
                ->filter(fn (Payment $payment) => $payment->type === Payment::TYPE_PLAN_FULL)
                ->sum(fn (Payment $payment) => $converter->convertMinor((int) $payment->app_fee_minor, $payment->currency));

            $totalBookingFeesMinor += $bookingFeesMinor;
            $totalPackageFeesMinor += $packageFeesMinor;

            return [
                $userRequest->formatted_order_number ? ('#'.$userRequest->formatted_order_number) : '-',
                $userRequest->user?->name ?? '-',
                $userRequest->country?->name ?? $userRequest->plan?->country?->name ?? '-',
                number_format($bookingFeesMinor / 100, 2),
                number_format($packageFeesMinor / 100, 2),
                $statusOptions[$userRequest->status] ?? $userRequest->status,
                $userRequest->created_at?->format('Y-m-d H:i') ?? '-',
            ];
        })->all();

        $headers = ['رقم الطلب', 'المستخدم', 'الدولة', 'رسوم الحجز', 'رسوم الباقات', 'الحالة', 'التاريخ'];

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new ArrayRowsExport($headers, $rows),
                'app-profits-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $countries = Country::query()->orderBy('name')->get(['id', 'name']);

        return $this->view(
            'أرباح التطبيق',
            $headers,
            $rows,
            $request,
            'app-profits',
            true,
            $this->buildDailyFilters($request, $dateFrom, $dateTo, [
                'country_id' => $countryId,
                'country_options' => $countries->pluck('name', 'id')->all(),
                'status' => $status,
                'status_options' => $statusOptions,
            ]),
            [
                ['label' => 'إجمالي رسوم الحجز', 'value' => number_format($totalBookingFeesMinor / 100, 2), 'icon' => 'receipt-2', 'tone' => 'info'],
                ['label' => 'إجمالي رسوم الباقات', 'value' => number_format($totalPackageFeesMinor / 100, 2), 'icon' => 'stack-3', 'tone' => 'success'],
                ['label' => 'إجمالي القيمة', 'value' => number_format(($totalBookingFeesMinor + $totalPackageFeesMinor) / 100, 2), 'icon' => 'coins', 'tone' => 'primary'],
                ['label' => 'عدد العمليات', 'value' => number_format(count($rows)), 'icon' => 'list-details', 'tone' => 'secondary'],
            ]
        );
    }

    public function walletPayments(Request $request)
    {
        $items = Payment::with(['userRequest.trainer', 'user'])
            ->where('payment_method', 'wallet')
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->latest()
            ->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new WalletPaymentsExport($items),
                'wallet-payments-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $rows = $items->map(function (Payment $p) {
            return [
                $p->id,
                $p->user?->name ?? '-',
                $p->userRequest?->trainer?->name ?? '-',
                $p->type,
                number_format($p->amount_minor / 100, 2),
                $p->created_at,
            ];
        })->all();

        return $this->view(
            'مدفوعات تمت عبر المحفظة',
            ['المعرف', 'المستخدم', 'المدرب', 'النوع', 'المبلغ', 'التاريخ'],
            $rows,
            $request,
            'wallet-payments',
            true
        );
    }
}
