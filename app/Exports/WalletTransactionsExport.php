<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WalletTransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $transactions
    ) {}

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'المستخدم',
            'المبلغ',
            'النوع',
            'الحالة',
            'ملاحظات',
            'تمت المعالجة بواسطة',
            'تاريخ المعالجة',
            'تاريخ الإنشاء',
        ];
    }

    public function map($transaction): array
    {
        $typeLabels = [
            \App\Models\WalletTransaction::TYPE_TOPUP_REQUEST => 'طلب إضافة رصيد',
            \App\Models\WalletTransaction::TYPE_REFUND => 'استرداد',
            \App\Models\WalletTransaction::TYPE_PAYMENT => 'دفع',
            \App\Models\WalletTransaction::TYPE_ADJUSTMENT => 'تعديل إداري',
        ];

        $statusLabels = [
            \App\Models\WalletTransaction::STATUS_PENDING => 'قيد الانتظار',
            \App\Models\WalletTransaction::STATUS_APPROVED => 'موافق عليه',
            \App\Models\WalletTransaction::STATUS_REJECTED => 'مرفوض',
        ];

        return [
            $transaction->id,
            $transaction->user?->name ?? $transaction->user_id,
            number_format($transaction->amountMajor(), 2),
            $typeLabels[$transaction->type] ?? $transaction->type,
            $statusLabels[$transaction->status] ?? $transaction->status,
            $transaction->notes ?? '-',
            $transaction->processedBy?->name ?? ($transaction->processed_by ?? '-'),
            $transaction->processed_at?->format('Y-m-d H:i:s') ?? '-',
            $transaction->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}


