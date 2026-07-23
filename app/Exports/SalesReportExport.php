<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\ReportCurrencyConverter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private readonly ReportCurrencyConverter $reportCurrencyConverter;

    public function __construct(
        private readonly \Illuminate\Support\Collection $payments
    ) {
        $this->reportCurrencyConverter = app(ReportCurrencyConverter::class);
    }

    public function collection()
    {
        return $this->payments;
    }

    public function headings(): array
    {
        return [
            'رقم الطلب',
            'معرف العميل',
            'اسم العميل',
            'جوال العميل',
            'المدرب',
            'جوال المدرب',
            'نوع الكورس',
            'الدولة',
            'المنطقة الأولى',
            'المنطقة الثانية',
            'المنطقة الثالثة',
            'الحي / المحلية',
            'المبلغ ('.ReportCurrencyConverter::REPORT_CURRENCY.')',
            'رسوم التطبيق ('.ReportCurrencyConverter::REPORT_CURRENCY.')',
            'النوع',
            'حالة الطلب',
            'حالة الدفع',
            'نوع الكوبون',
            'التاريخ',
        ];
    }

    public function map($payment): array
    {
        $requestStatusLabels = [
            \App\Models\UserRequest::STATUS_AWAITING_OFFERS => 'انتظار العروض',
            \App\Models\UserRequest::STATUS_CANCELLED => 'ملغي',
            \App\Models\UserRequest::STATUS_IN_TRAINING => 'قيد التدريب',
            \App\Models\UserRequest::STATUS_COMPLETED => 'مكتمل',
            \App\Models\UserRequest::STATUS_PENDING_PAYMENT => 'قيد الدفع',
            \App\Models\UserRequest::STATUS_OFFER_SELECTED => 'تم اختيار العرض',
            \App\Models\UserRequest::STATUS_PAID => 'مدفوع',
        ];

        return [
            $payment->userRequest?->formatted_order_number ?? $payment->userRequest?->order_number ?? '-',
            $payment->user_id ?? '-',
            $payment->user?->name ?? '-',
            $payment->user?->phone_with_cc ?? '-',
            $payment->userRequest?->trainer?->name ?? '-',
            $payment->userRequest?->trainer?->phone_with_cc ?? '-',
            $payment->userRequest?->plan?->title ?? '-',
            $payment->userRequest?->country?->name ?? $payment->userRequest?->plan?->country?->name ?? '-',
            $payment->userRequest?->area_level_1 ?? '-',
            $payment->userRequest?->area_level_2 ?? '-',
            $payment->userRequest?->area_level_3 ?? '-',
            $payment->userRequest?->locality ?? '-',
            $this->reportCurrencyConverter->formatConvertedMinor($payment->grossAmountMinor(), $payment->currency),
            $this->reportCurrencyConverter->formatConvertedMinor((int) $payment->app_fee_minor, $payment->currency),
            \App\Models\Payment::reportTypeLabels()[$payment->type] ?? $payment->type,
            $requestStatusLabels[$payment->userRequest?->status] ?? ($payment->userRequest?->status ?? '-'),
            \App\Models\Payment::statusLabelFor($payment->status),
            $this->couponType($payment),
            $payment->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function couponType($payment): string
    {
        $value = $payment->coupon_type
            ?? $payment->coupon?->type
            ?? $payment->userRequest?->coupon_type
            ?? $payment->userRequest?->coupon?->type
            ?? null;

        $value = trim((string) $value);

        return $value === '' ? '-' : $value;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
