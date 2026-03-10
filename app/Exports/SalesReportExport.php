<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $payments
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
            'الدولة',
            'المنطقة الأولى',
            'المنطقة الثانية',
            'المنطقة الثالثة',
            'الحي / المحلية',
            'المبلغ',
            'رسوم التطبيق',
            'النوع',
            'الحالة',
            'التاريخ',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->user?->name ?? $payment->user_id,
            $payment->userRequest?->country?->name ?? $payment->userRequest?->plan?->country?->name ?? '-',
            $payment->userRequest?->area_level_1 ?? '-',
            $payment->userRequest?->area_level_2 ?? '-',
            $payment->userRequest?->area_level_3 ?? '-',
            $payment->userRequest?->locality ?? '-',
            number_format($payment->amount_minor / 100, 2) . ' ' . $payment->currency,
            number_format($payment->app_fee_minor / 100, 2) . ' ' . $payment->currency,
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
