<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VatReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $payments,
        private readonly float $vatPercent
    ) {}

    public function collection()
    {
        return $this->payments;
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'المستخدم',
            'المبلغ',
            'النوع',
            'ضريبة القيمة المضافة',
            'التاريخ',
        ];
    }

    public function map($payment): array
    {
        $vatMinor = (int) round($payment->amount_minor * ($this->vatPercent / 100));
        return [
            $payment->id,
            $payment->user?->name ?? $payment->user_id,
            number_format($payment->amount_minor / 100, 2) . ' ' . $payment->currency,
            $payment->type,
            number_format($vatMinor / 100, 2),
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

