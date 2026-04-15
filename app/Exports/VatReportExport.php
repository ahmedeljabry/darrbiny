<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\ReportCurrencyConverter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VatReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private readonly ReportCurrencyConverter $reportCurrencyConverter;

    public function __construct(
        private readonly \Illuminate\Support\Collection $payments,
        private readonly float $vatPercent
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
            'المبلغ (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'النوع',
            'ضريبة القيمة المضافة (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'التاريخ',
        ];
    }

    public function map($payment): array
    {
        $vatMinor = (int) round($payment->amount_minor * ($this->vatPercent / 100));
        return [
            $payment->id,
            $payment->user?->name ?? $payment->user_id,
            $this->reportCurrencyConverter->formatConvertedMinor((int) $payment->amount_minor, $payment->currency),
            $payment->type,
            $this->reportCurrencyConverter->formatConvertedMinor($vatMinor, $payment->currency),
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

