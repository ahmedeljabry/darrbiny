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
            'المستخدم',
            'الدولة',
            'المنطقة الأولى',
            'المنطقة الثانية',
            'المنطقة الثالثة',
            'الحي / المحلية',
            'المبلغ (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'رسوم التطبيق (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'النوع',
            'الحالة',
            'التاريخ',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->userRequest?->formatted_order_number ?? $payment->userRequest?->order_number ?? '-',
            $payment->user?->name ?? $payment->user_id,
            $payment->userRequest?->country?->name ?? $payment->userRequest?->plan?->country?->name ?? '-',
            $payment->userRequest?->area_level_1 ?? '-',
            $payment->userRequest?->area_level_2 ?? '-',
            $payment->userRequest?->area_level_3 ?? '-',
            $payment->userRequest?->locality ?? '-',
            $this->reportCurrencyConverter->formatConvertedMinor((int) $payment->amount_minor, $payment->currency),
            $this->reportCurrencyConverter->formatConvertedMinor((int) $payment->app_fee_minor, $payment->currency),
            $payment->type,
            $payment->status,
            $payment->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
