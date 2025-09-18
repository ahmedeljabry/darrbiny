<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\Admin\ReportsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class ReportsController extends BaseController
{
    public function index(Request $request, ReportsService $service)
    {
        $from = $request->date('from');
        $to = $request->date('to');
        $payments = $service->recentPayments($from, $to, 50);
        return view('admin.reports.index', compact('payments','from','to'));
    }

    public function sales(Request $request, ReportsService $service)
    {
        $from = $request->date('from');
        $to = $request->date('to');
        ['payments' => $payments, 'totalMinor' => $total] = $service->sales($from, $to);
        return view('admin.reports.sales', compact('payments','from','to','total'));
    }

    public function payments(Request $request, ReportsService $service)
    {
        $payments = $service->paymentsList($request->query('type'), $request->query('status'));
        return view('admin.reports.payments', compact('payments'));
    }

    public function subscriptions(Request $request, ReportsService $service)
    {
        $subs = $service->subscriptionsList($request->query('status'));
        return view('admin.reports.subscriptions', compact('subs'));
    }
}
