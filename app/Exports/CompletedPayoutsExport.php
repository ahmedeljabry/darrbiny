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
            'تم سحبه',
            'المتبقي',
            'حالة السحب',
            'تاريخ الدفعة',
        ];
    }

    public function map($payment): array
    {
        $trainer = $payment->userRequest?->trainer;
        $payoutStatus = $payment->userRequest?->payout?->status;
        $payoutStatusLabel = match ($payoutStatus) {
            \App\Models\Payout::STATUS_SENT => 'منفذة',
            \App\Models\Payout::STATUS_APPROVED => 'معتمدة',
            \App\Models\Payout::STATUS_FAILED => 'فشلت',
            \App\Models\Payout::STATUS_PENDING_REVIEW => 'غير منفذة',
            default => 'غير منفذة',
        };
        $executionStatus = (string) ($payment->payout_execution_status ?? $payoutStatusLabel);

        return [
            $trainer?->name ?? '-',
            $trainer?->phone_with_cc ?? '-',
            $trainer?->country?->name
                ?? $trainer?->trainerProfile?->country?->name
                ?? $payment->userRequest?->country?->name
                ?? $payment->userRequest?->plan?->country?->name
                ?? '-',
            $trainer?->bank_name ?? '-',
            $trainer?->bank_account ?? '-',
            $trainer?->iban ?? '-',
            number_format($payment->trainer_net_minor / 100, 2),
            number_format(((int) ($payment->executed_withdrawal_minor ?? 0)) / 100, 2),
            number_format(((int) ($payment->remaining_trainer_net_minor ?? $payment->trainer_net_minor)) / 100, 2),
            $executionStatus,
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
