<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrizeRedemptionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $redemptions
    ) {}

    public function collection()
    {
        return $this->redemptions;
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'المستخدم',
            'الجائزة',
            'النقاط المستخدمة',
            'الحالة',
            'سبب الرفض',
            'تاريخ الإنشاء',
        ];
    }

    public function map($redemption): array
    {
        $statuses = [
            'pending' => 'قيد الانتظار',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
        ];

        return [
            $redemption->id,
            $redemption->user?->name ?? $redemption->user_id,
            $redemption->reward?->title ?? $redemption->reward_id,
            $redemption->points_spent,
            $statuses[$redemption->status] ?? $redemption->status,
            $redemption->rejection_reason ?? '-',
            $redemption->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

