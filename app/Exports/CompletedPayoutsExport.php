<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompletedPayoutsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
            'المدرب',
            'الجوال',
            'البنك',
            'IBAN',
            'حساب',
            'صافي المدرب',
            'تاريخ الدفعة',
        ];
    }

    public function map($payment): array
    {
        $trainer = $payment->userRequest?->trainer;
        return [
            $payment->id,
            $trainer?->name ?? '-',
            $trainer?->phone_with_cc ?? '-',
            $trainer?->bank_name ?? '-',
            $trainer?->iban ?? '-',
            $trainer?->bank_account ?? '-',
            number_format($payment->trainer_net_minor / 100, 2),
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

