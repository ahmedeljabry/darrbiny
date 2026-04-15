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
            'المعرف',
            'المستخدم',
            'الطلب',
            'المبلغ (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'النوع',
            'الحالة',
            'المزود',
            'التاريخ',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->user?->name ?? $payment->user_id,
            $payment->user_request_id,
            $this->reportCurrencyConverter->formatConvertedMinor((int) $payment->amount_minor, $payment->currency),
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
