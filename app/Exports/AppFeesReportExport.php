<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\ReportCurrencyConverter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AppFeesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
            'مرجع الدفع',
            'رسوم التطبيق (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'النوع',
            'التاريخ',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->userRequest?->formatted_order_number ?? $payment->userRequest?->order_number ?? '-',
            $payment->user?->name ?? $payment->user_id,
            $payment->id,
            $this->reportCurrencyConverter->formatConvertedMinor((int) $payment->app_fee_minor, $payment->currency),
            $payment->type,
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

