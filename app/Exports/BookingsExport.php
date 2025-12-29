<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $bookings
    ) {}

    public function collection()
    {
        return $this->bookings;
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'المستخدم',
            'المدرب',
            'الخطة',
            'الحالة',
            'تاريخ البدء',
            'المبلغ المدفوع',
            'العملة',
            'تاريخ الإنشاء',
        ];
    }

    public function map($booking): array
    {
        $statuses = [
            \App\Models\UserRequest::STATUS_PENDING_PAYMENT => 'قيد الدفع',
            \App\Models\UserRequest::STATUS_AWAITING_OFFERS => 'في انتظار العروض',
            \App\Models\UserRequest::STATUS_OFFER_SELECTED => 'تم اختيار العرض',
            \App\Models\UserRequest::STATUS_PAID => 'مدفوع',
            \App\Models\UserRequest::STATUS_IN_TRAINING => 'قيد التدريب',
            \App\Models\UserRequest::STATUS_COMPLETED => 'مكتمل',
            \App\Models\UserRequest::STATUS_CANCELLED => 'ملغي',
        ];

        return [
            $booking->id,
            $booking->user?->name ?? $booking->user_id,
            $booking->trainer?->name ?? ($booking->trainer_id ?: '-'),
            $booking->plan?->title ?? $booking->plan_id,
            $statuses[$booking->status] ?? $booking->status,
            $booking->start_date?->toDateString(),
            number_format($booking->total_paid_minor / 100, 2),
            $booking->currency ?? 'USD',
            $booking->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}



