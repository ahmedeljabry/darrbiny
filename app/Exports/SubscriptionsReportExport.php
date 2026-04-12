<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscriptionsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $subscriptions
    ) {}

    public function collection()
    {
        return $this->subscriptions;
    }

    public function headings(): array
    {
        return [
            'رقم الطلب',
            'المستخدم',
            'الخطة',
            'المبلغ',
            'الحالة',
            'تاريخ البدء',
            'تاريخ الإنشاء',
        ];
    }

    public function map($subscription): array
    {
        $amountMinor = max(
            (int) ($subscription->total_paid_minor ?? 0),
            method_exists($subscription, 'totalSuccessfulPaymentsMinor')
                ? $subscription->totalSuccessfulPaymentsMinor()
                : 0
        );

        return [
            $subscription->id,
            $subscription->user?->name ?? $subscription->user_id,
            $subscription->plan?->title ?? $subscription->plan_id,
            number_format($amountMinor / 100, 2) . ' ' . ($subscription->currency ?? 'SAR'),
            $subscription->status,
            $subscription->start_date?->toDateString(),
            $subscription->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

