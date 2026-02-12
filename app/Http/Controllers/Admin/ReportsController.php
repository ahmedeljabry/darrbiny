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
        ['payments' => $payments, 'totalMinor' => $total] = $service->sales($from, $to);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new SalesReportExport($payments->getCollection()),
                'sales-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.reports.sales', compact('payments','from','to','total'));
    }

    public function payments(Request $request, ReportsService $service)
    {
        $payments = $service->paymentsList($request->query('type'), $request->query('status'));
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new PaymentsReportExport($payments->getCollection()),
                'payments-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        return view('admin.reports.payments', compact('payments'));
    }

    public function subscriptions(Request $request, ReportsService $service)
    {
        $subs = $service->subscriptionsList($request->query('status'));
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new SubscriptionsReportExport($subs->getCollection()),
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
                new PlanSalesReportExport($payments->getCollection()),
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
                new AppFeesReportExport($payments->getCollection()),
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
                new VatReportExport($payments->getCollection(), $vatPercent),
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

        $date = \Carbon\CarbonImmutable::parse($value);
        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }
}
