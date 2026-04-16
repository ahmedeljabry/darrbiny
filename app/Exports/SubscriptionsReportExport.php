<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\ReportCurrencyConverter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscriptionsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private readonly ReportCurrencyConverter $reportCurrencyConverter;

    public function __construct(
        private readonly \Illuminate\Support\Collection $subscriptions
    ) {
        $this->reportCurrencyConverter = app(ReportCurrencyConverter::class);
    }

    public function collection()
    {
        return $this->subscriptions;
    }

    public function headings(): array
    {
        return [
            'رقم الطلب',
            'المستخدم',
            'الخطة',
            'المبلغ (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'الحالة',
            'تاريخ البدء',
            'تاريخ الإنشاء',
        ];
    }

    public function map($subscription): array
    {
        $amountMinor = max(
            (int) ($subscription->total_paid_minor ?? 0),
            method_exists($subscription, 'totalSuccessfulPaymentsMinor')
                ? $subscription->totalSuccessfulPaymentsMinor()
                : 0
        );

        return [
            $subscription->formatted_order_number ?? $subscription->order_number ?? $subscription->id,
            $subscription->user?->name ?? $subscription->user_id,
            $subscription->plan?->title ?? $subscription->plan_id,
            $this->reportCurrencyConverter->formatConvertedMinor($amountMinor, $subscription->currency ?? 'SAR'),
            $subscription->status,
            $subscription->start_date?->toDateString(),
            $subscription->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

