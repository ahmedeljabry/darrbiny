<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Payment;
use App\Support\ReportCurrencyConverter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private readonly ReportCurrencyConverter $reportCurrencyConverter;

    public function __construct(
        private readonly \Illuminate\Support\Collection $bookings
    ) {
        $this->reportCurrencyConverter = app(ReportCurrencyConverter::class);
    }

    public function collection()
    {
        return $this->bookings;
    }

    public function headings(): array
    {
        return [
            'رقم الطلب',
            'المستخدم',
            'المدرب',
            'الخطة',
            'الحالة',
            'تاريخ البدء',
            'الدفعات',
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

        $payments = $booking->payments ?? collect();
        $paymentsSummary = $payments->isNotEmpty()
            ? $payments->map(function (Payment $payment): string {
                return sprintf(
                    '%s: %s (%s)',
                    $payment->typeLabel(),
                    $this->reportCurrencyConverter->formatConvertedMinor((int) $payment->amount_minor, $payment->currency),
                    $payment->statusLabel()
                );
            })->implode(' | ')
            : '-';

        return [
            $booking->formatted_order_number ?? $booking->order_number ?? $booking->id,
            $booking->user?->name ?? $booking->user_id,
            $booking->trainer?->name ?? ($booking->trainer_id ?: '-'),
            $booking->plan?->title ?? $booking->plan_id,
            $statuses[$booking->status] ?? $booking->status,
            $booking->start_date?->toDateString(),
            $paymentsSummary,
            ReportCurrencyConverter::REPORT_CURRENCY,
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



