<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\AppFeesReportExport;
use App\Exports\PaymentsReportExport;
use App\Exports\PlanSalesReportExport;
use App\Exports\SalesReportExport;
use App\Exports\SubscriptionsReportExport;
use App\Exports\VatReportExport;
use App\Services\Admin\ReportsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends BaseController
{
    public function index(Request $request, ReportsService $service)
    {
        $from = $this->parseDate($request->input('from'));
        $to = $this->parseDate($request->input('to'), true);
        $payments = $service->recentPayments($from, $to, 50);
        return view('admin.reports.index', compact('payments','from','to'));
    }

    public function sales(Request $request, ReportsService $service)
    {
        $from = $this->parseDate($request->input('from'));
        $to = $this->parseDate($request->input('to'), true);
        $paymentType = $this->parseSalesPaymentType($request->query('type'));
        ['payments' => $payments, 'totalMinor' => $total] = $service->sales($from, $to, $paymentType);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new SalesReportExport($service->salesCollection($from, $to, $paymentType)),
                'sales-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.reports.sales', compact('payments','from','to','total','paymentType'));
    }

    public function payments(Request $request, ReportsService $service)
    {
        $type = $request->query('type');
        $status = $request->query('status');
        $payments = $service->paymentsList($type, $status);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new PaymentsReportExport($service->paymentsCollection($type, $status)),
                'payments-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.reports.payments', compact('payments'));
    }

    public function subscriptions(Request $request, ReportsService $service)
    {
        $status = $request->query('status');
        $subs = $service->subscriptionsList($status);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new SubscriptionsReportExport($service->subscriptionsCollection($status)),
                'subscriptions-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.reports.subscriptions', compact('subs'));
    }

    public function planSales(Request $request, ReportsService $service)
    {
        $from = $this->parseDate($request->input('from'));
        $to = $this->parseDate($request->input('to'), true);
        ['payments' => $payments, 'totalMinor' => $total] = $service->planSales($from, $to);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new PlanSalesReportExport($service->planSalesCollection($from, $to)),
                'plan-sales-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.reports.plan-sales', compact('payments', 'from', 'to', 'total'));
    }

    public function appFees(Request $request, ReportsService $service)
    {
        $from = $this->parseDate($request->input('from'));
        $to = $this->parseDate($request->input('to'), true);
        ['payments' => $payments, 'totalMinor' => $total] = $service->appFees($from, $to);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new AppFeesReportExport($service->appFeesCollection($from, $to)),
                'app-fees-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.reports.app-fees', compact('payments', 'from', 'to', 'total'));
    }

    public function vat(Request $request, ReportsService $service)
    {
        $from = $this->parseDate($request->input('from'));
        $to = $this->parseDate($request->input('to'), true);
        ['payments' => $payments, 'vatPercent' => $vatPercent, 'vatTotalMinor' => $vatTotalMinor] = $service->vatReport($from, $to);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new VatReportExport($service->vatCollection($from, $to), $vatPercent),
                'vat-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.reports.vat', compact('payments', 'from', 'to', 'vatPercent', 'vatTotalMinor'));
    }

    private function parseDate(?string $value, bool $endOfDay = false): ?\Carbon\CarbonImmutable
    {
        $value = is_string($value) ? trim($value) : null;
        if ($value === '' || $value === null) {
            return null;
        }

        try {
            $date = \Carbon\CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function parseSalesPaymentType(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === '' || $value === null) {
            return null;
        }

        return in_array($value, [
            \App\Models\Payment::TYPE_PLAN_PARTIAL,
            \App\Models\Payment::TYPE_PLAN_FULL,
        ], true)
            ? $value
            : null;
    }
}
