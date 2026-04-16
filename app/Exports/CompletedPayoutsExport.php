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
            'المدرب',
            'الجوال',
            'الدولة',
            'البنك',
            'رقم الحساب',
            'IBAN',
            'صافي المدرب',
            'تاريخ الدفعة',
        ];
    }

    public function map($payment): array
    {
        $trainer = $payment->userRequest?->trainer;
        return [
            $trainer?->name ?? '-',
            $trainer?->phone_with_cc ?? '-',
            $trainer?->country?->name ?? '-',
            $trainer?->bank_name ?? '-',
            $trainer?->bank_account ?? '-',
            $trainer?->iban ?? '-',
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



