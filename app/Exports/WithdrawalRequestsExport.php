<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\WalletTransaction;
use App\Support\ReportCurrencyConverter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WithdrawalRequestsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $requests,
        private readonly ReportCurrencyConverter $reportCurrencyConverter
    ) {}

    public function collection()
    {
        return $this->requests;
    }

    public function headings(): array
    {
        return [
            'المستخدم',
            'الاسم الحقيقي',
            'الجوال',
            'الدولة',
            'نوع الحساب',
            'اسم البنك',
            'رقم الحساب',
            'IBAN',
            'المبلغ (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'مبلغ المحفظة',
            'عملة المحفظة',
            'الحالة',
            'تاريخ الطلب',
        ];
    }

    public function map($withdrawalRequest): array
    {
        $user = $withdrawalRequest->user;
        $isTrainer = ($user?->user_type?->value ?? null) === 'captain';

        $statusLabels = [
            WalletTransaction::STATUS_PENDING => 'معلق',
            WalletTransaction::STATUS_APPROVED => 'منفذ',
            WalletTransaction::STATUS_REJECTED => 'مرفوض',
        ];

        return [
            $user?->name ?? '-',
            $user?->bank_account_name ?? '-',
            $user?->phone_with_cc ?? '-',
            $user?->country?->name ?? $user?->bankCountry?->name ?? '-',
            $isTrainer ? 'مدرب' : 'طالب',
            $user?->bank_name ?? '-',
            $user?->bank_account ?? '-',
            $user?->iban ?? '-',
            number_format($withdrawalRequest->reportAmountMinor($this->reportCurrencyConverter) / 100, 2),
            number_format($withdrawalRequest->amountMajor(), 2),
            $withdrawalRequest->transactionCurrency(),
            $statusLabels[$withdrawalRequest->status] ?? $withdrawalRequest->status,
            $withdrawalRequest->created_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
