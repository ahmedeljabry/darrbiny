<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WalletBalancesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $users
    ) {}

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'الاسم',
            'الجوال',
            'الرصيد',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name ?? '-',
            $user->phone_with_cc ?? '-',
            $user->points_balance,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

