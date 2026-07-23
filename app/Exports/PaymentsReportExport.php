<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Payment;
use App\Support\ReportCurrencyConverter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
            'المبلغ ('.ReportCurrencyConverter::REPORT_CURRENCY.')',
            'النوع',
            'الحالة',
            'المزود',
            'التاريخ',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->userRequest?->formatted_order_number ?? $payment->userRequest?->order_number ?? '-',
            $payment->user?->name ?? $payment->user_id,
            $this->reportCurrencyConverter->formatConvertedMinor($payment->grossAmountMinor(), $payment->currency),
            Payment::typeLabelFor($payment->type),
            Payment::statusLabelFor($payment->status),
            $payment->payment_method,
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
