<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ActiveCoursesExport;
use App\Exports\CompletedPayoutsExport;
use App\Exports\PointsBalancesExport;
use App\Exports\WalletBalancesExport;
use App\Exports\WalletPaymentsExport;
use App\Models\Payment;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\UserScheduleProgress;
use Carbon\Carbon;
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
        }, $filename . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function view(
        string $title,
        array $headers,
        array $rows,
        Request $request,
        string $filename,
        bool $supportsExcel = false,
        ?string $dateFilter = null
    ) {
        if ($export = $this->maybeExportCsv($request, $filename, $headers, $rows)) {
            return $export;
        }

        return view('admin.reports.custom', compact(
            'title',
            'headers',
            'rows',
            'supportsExcel',
            'dateFilter'
        ));
    }

    public function completedPayouts(Request $request)
    {
        $date = $request->date('date') ?? Carbon::today();

        $payments = Payment::with(['userRequest.trainer'])
            ->where('type', Payment::TYPE_PLAN_FULL)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->whereHas('userRequest', fn($q) => $q->where('status', UserRequest::STATUS_COMPLETED))
            ->whereDate('created_at', $date)
            ->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new CompletedPayoutsExport($payments),
                'completed-payouts-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $rows = $payments->map(function (Payment $p) {
            $trainer = $p->userRequest?->trainer;
            return [
                $p->id,
                $trainer?->name ?? '-',
                $trainer?->phone_with_cc ?? '-',
                $trainer?->bank_name ?? '-',
                $trainer?->iban ?? '-',
                $trainer?->bank_account ?? '-',
                number_format($p->trainer_net_minor / 100, 2),
                $p->created_at,
            ];
        })->all();

        return $this->view(
            'الكورسات المكتملة ومستحقات المدرب (يومي)',
            ['المعرف', 'المدرب', 'الجوال', 'البنك', 'IBAN', 'حساب', 'صافي المدرب', 'تاريخ الدفعة'],
            $rows,
            $request,
            'completed-payouts',
            true,
            $date->toDateString()
        );
    }

    public function activeCourses(Request $request)
    {
        $courses = UserRequest::with(['trainer', 'user', 'payments' => function ($q) {
            $q->where('status', Payment::STATUS_SUCCEEDED)->orderByDesc('created_at');
        }])->where('status', UserRequest::STATUS_IN_TRAINING)->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new ActiveCoursesExport($courses),
                'active-courses-' . now()->format('Y-m-d') . '.xlsx'
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

        $rows = $requests->map(fn($r) => [
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
        $date = $request->date('date') ?? Carbon::today();
        $items = UserScheduleProgress::with(['userRequest.user', 'userRequest.trainer'])
            ->where('status', UserScheduleProgress::STATUS_REJECTED)
            ->whereDate('created_at', $date)
            ->get();

        $rows = $items->map(function (UserScheduleProgress $p) {
            return [
                $p->id,
                $p->userRequest?->id ?? '-',
                $p->userRequest?->user?->name ?? '-',
                $p->userRequest?->trainer?->name ?? '-',
                $p->day_number,
                $p->rejection_reason,
                $p->created_at,
            ];
        })->all();

        return $this->view(
            'الكورسات مع رفض في الإنجاز اليومي',
            ['المعرف', 'الطلب', 'المستخدم', 'المدرب', 'اليوم', 'سبب الرفض', 'التاريخ'],
            $rows,
            $request,
            'rejected-progress',
            false,
            $date->toDateString()
        );
    }

    public function walletBalances(Request $request)
    {
        $users = User::where('points_balance', '>', 0)->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new WalletBalancesExport($users),
                'wallet-balances-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $rows = $users->map(fn(User $u) => [
            $u->id,
            $u->name ?? '-',
            $u->phone_with_cc ?? '-',
            $u->points_balance,
        ])->all();

        return $this->view(
            'المحافظ التي تحتوي مبالغ',
            ['المعرف', 'الاسم', 'الجوال', 'الرصيد'],
            $rows,
            $request,
            'wallet-balances',
            true
        );
    }

    public function pointsBalances(Request $request)
    {
        $users = User::with('roles')->get();

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new PointsBalancesExport($users),
                'points-balances-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $rows = $users->map(fn(User $u) => [
            $u->id,
            $u->name ?? '-',
            $u->phone_with_cc ?? '-',
            $u->hasRole('TRAINER') ? 'مدرب' : 'مستخدم',
            $u->points_balance,
        ])->all();

        return $this->view(
            'تقرير النقاط لكل مستخدم/مدرب',
            ['المعرف', 'الاسم', 'الجوال', 'النوع', 'النقاط'],
            $rows,
            $request,
            'points-balances',
            true
        );
    }

    public function rewardRedemptions(Request $request)
    {
        $items = RewardRedemption::with(['user', 'reward'])->latest()->get();

        $rows = $items->map(fn(RewardRedemption $r) => [
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
            'reward-redemptions'
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
                'wallet-payments-' . now()->format('Y-m-d') . '.xlsx'
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
