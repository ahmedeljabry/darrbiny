<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\AppFeesReportExport;
use App\Exports\PaymentsReportExport;
use App\Exports\PlanSalesReportExport;
use App\Exports\SalesReportExport;
use App\Exports\SubscriptionsReportExport;
use App\Exports\VatReportExport;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\UserRequest;
use App\Services\Admin\ReportsService;
use App\Support\ReportCurrencyConverter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends BaseController
{
    public function index(Request $request, ReportsService $service, ReportCurrencyConverter $reportCurrencyConverter)
    {
        $from = $this->parseDate($request->input('from'));
        $to = $this->parseDate($request->input('to'), true);
        $payments = $service->recentPayments($from, $to, 50);
        $previewTotalMinor = $reportCurrencyConverter->sumCollectionMinorToReportCurrency($payments);

        return view('admin.reports.index', compact('payments', 'from', 'to', 'previewTotalMinor'));
    }

    public function sales(Request $request, ReportsService $service)
    {
        $filters = $this->salesFilters($request);
        ['payments' => $payments, 'totalMinor' => $total, 'count' => $count, 'averageMinor' => $averageMinor] = $service->salesReport($filters);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new SalesReportExport($service->salesReportCollection($filters)),
                'sales-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
        
        $paymentMethods = $this->paymentMethodOptions();
        $countries = $this->countryOptions();
        $typeOptions = $this->reportTypeOptions();

        return view('admin.reports.sales', compact(
            'payments',
            'total',
            'count',
            'averageMinor',
            'filters',
            'paymentMethods',
            'countries',
            'typeOptions'
        ));
    }

    public function payments(Request $request, ReportsService $service)
    {
        $filters = $this->paymentFilters($request, allowStatus: true, allowPlan: true);
        ['payments' => $payments, 'totalMinor' => $totalMinor, 'count' => $count] = $service->paymentsReport($filters);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new PaymentsReportExport($service->paymentsCollection($filters)),
                'payments-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $paymentMethods = $this->paymentMethodOptions();
        $countries = $this->countryOptions();
        $plans = $this->planOptions();
        $statusOptions = Payment::statusLabels();
        $typeOptions = $this->reportTypeOptions();
        
        return view('admin.reports.payments', compact(
            'payments',
            'totalMinor',
            'count',
            'filters',
            'paymentMethods',
            'countries',
            'plans',
            'statusOptions',
            'typeOptions'
        ));
    }

    public function subscriptions(Request $request, ReportsService $service)
    {
        $filters = $this->subscriptionFilters($request);
        ['subscriptions' => $subs, 'count' => $count] = $service->subscriptionsReport($filters);
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new SubscriptionsReportExport($service->subscriptionsCollection($filters)),
                'subscriptions-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $countries = $this->countryOptions();
        $plans = $this->planOptions();
        $statusOptions = $this->subscriptionStatusOptions();
        
        return view('admin.reports.subscriptions', compact(
            'subs',
            'count',
            'filters',
            'countries',
            'plans',
            'statusOptions'
        ));
    }

    public function planSales(Request $request, ReportsService $service)
    {
        $filters = $this->paymentFilters($request, allowType: false, allowStatus: false, allowPlan: false);
        ['payments' => $payments, 'totalMinor' => $total, 'count' => $count, 'averageMinor' => $averageMinor] = $service->planSales(
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters
        );
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new PlanSalesReportExport($service->planSalesCollection($filters['from'] ?? null, $filters['to'] ?? null, $filters)),
                'plan-sales-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $paymentMethods = $this->paymentMethodOptions();
        $countries = $this->countryOptions();
        
        return view('admin.reports.plan-sales', compact(
            'payments',
            'total',
            'count',
            'averageMinor',
            'filters',
            'paymentMethods',
            'countries'
        ));
    }

    public function appFees(Request $request, ReportsService $service)
    {
        $filters = $this->paymentFilters($request, allowType: false, allowStatus: false, allowPlan: false);
        ['payments' => $payments, 'totalMinor' => $total, 'count' => $count, 'averageMinor' => $averageMinor] = $service->appFees(
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters
        );
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new AppFeesReportExport($service->appFeesCollection($filters['from'] ?? null, $filters['to'] ?? null, $filters)),
                'app-fees-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $paymentMethods = $this->paymentMethodOptions();
        $countries = $this->countryOptions();
        
        return view('admin.reports.app-fees', compact(
            'payments',
            'total',
            'count',
            'averageMinor',
            'filters',
            'paymentMethods',
            'countries'
        ));
    }

    public function vat(Request $request, ReportsService $service)
    {
        $filters = $this->paymentFilters($request, allowStatus: false, allowPlan: false);
        ['payments' => $payments, 'vatPercent' => $vatPercent, 'vatTotalMinor' => $vatTotalMinor, 'count' => $count] = $service->vatReport(
            $filters['from'] ?? null,
            $filters['to'] ?? null,
            $filters
        );
        
        if ($request->query('export') === 'excel') {
            return Excel::download(
                new VatReportExport($service->vatCollection($filters['from'] ?? null, $filters['to'] ?? null, $filters), $vatPercent),
                'vat-report-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $paymentMethods = $this->paymentMethodOptions();
        $countries = $this->countryOptions();
        $typeOptions = $this->reportTypeOptions();
        
        return view('admin.reports.vat', compact(
            'payments',
            'vatPercent',
            'vatTotalMinor',
            'count',
            'filters',
            'paymentMethods',
            'countries',
            'typeOptions'
        ));
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

    private function paymentFilters(
        Request $request,
        bool $allowType = true,
        bool $allowStatus = true,
        bool $allowPlan = true
    ): array {
        $filters = [
            'search' => $this->parseSearch($request->query('search')),
            'payment_method' => $this->parseOption($request->query('payment_method'), $this->paymentMethodOptions()->all()),
            'country_id' => $this->parseIdFilter($request->query('country_id')),
            'from' => $this->parseDate($request->query('from')),
            'to' => $this->parseDate($request->query('to'), true),
        ];

        if ($allowType) {
            $allowedTypes = array_keys(Payment::typeLabels());
            $filters['type'] = $this->parseOption($request->query('type'), $allowedTypes);
        }

        if ($allowStatus) {
            $allowedStatuses = array_keys(Payment::statusLabels());
            $filters['status'] = $this->parseOption($request->query('status'), $allowedStatuses);
        }

        if ($allowPlan) {
            $filters['plan_id'] = $this->parseIdFilter($request->query('plan_id'));
        }

        return $filters;
    }

    private function salesFilters(Request $request): array
    {
        $filters = $this->paymentFilters($request, allowStatus: false, allowPlan: false);
        $filters['type'] = $this->parseSalesPaymentType($request->query('type'));

        return $filters;
    }

    private function subscriptionFilters(Request $request): array
    {
        return [
            'search' => $this->parseSearch($request->query('search')),
            'status' => $this->parseOption($request->query('status'), array_keys($this->subscriptionStatusOptions())),
            'country_id' => $this->parseIdFilter($request->query('country_id')),
            'plan_id' => $this->parseIdFilter($request->query('plan_id')),
            'from' => $this->parseDate($request->query('from')),
            'to' => $this->parseDate($request->query('to'), true),
        ];
    }

    private function parseSearch(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' || $value === null ? null : $value;
    }

    private function parseIdFilter(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' || $value === null ? null : $value;
    }

    private function parseOption(?string $value, array $allowed): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === '' || $value === null) {
            return null;
        }

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function paymentMethodOptions(): Collection
    {
        return Payment::query()
            ->whereNotNull('payment_method')
            ->where('payment_method', '<>', '')
            ->distinct()
            ->orderBy('payment_method')
            ->pluck('payment_method');
    }

    private function countryOptions(): Collection
    {
        return Country::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function planOptions(): Collection
    {
        return Plan::query()
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    private function subscriptionStatusOptions(): array
    {
        return [
            UserRequest::STATUS_PENDING_PAYMENT => 'قيد الدفع',
            UserRequest::STATUS_AWAITING_OFFERS => 'بانتظار العروض',
            UserRequest::STATUS_OFFER_SELECTED => 'تم اختيار العرض',
            UserRequest::STATUS_PAID => 'مدفوع',
            UserRequest::STATUS_IN_TRAINING => 'قيد التدريب',
            UserRequest::STATUS_COMPLETED => 'مكتمل',
            UserRequest::STATUS_CANCELLED => 'ملغي',
        ];
    }

    private function reportTypeOptions(): array
    {
        return [
            ...Payment::typeLabels(),
            Payment::TYPE_PLAN_PARTIAL => 'رسوم الحجز',
        ];
    }
}
