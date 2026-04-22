<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\ReportCurrencyConverter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CancellationRequestsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private readonly ReportCurrencyConverter $reportCurrencyConverter;

    public function __construct(
        private readonly \Illuminate\Support\Collection $requests
    ) {
        $this->reportCurrencyConverter = app(ReportCurrencyConverter::class);
    }

    public function collection()
    {
        return $this->requests;
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'المستخدم',
            'الخطة',
            'قيمة الباقة',
            'المبلغ المدفوع',
            'السبب',
            'الحالة',
            'ملاحظات الإدارة',
            'تمت المعالجة بواسطة',
            'تاريخ المعالجة',
            'تاريخ الإنشاء',
        ];
    }

    public function map($request): array
    {
        $statuses = [
            \App\Models\CancellationRequest::STATUS_PENDING => 'قيد الانتظار',
            \App\Models\CancellationRequest::STATUS_APPROVED => 'موافق عليه',
            \App\Models\CancellationRequest::STATUS_REJECTED => 'مرفوض',
        ];

        $userRequest = $request->userRequest;
        $fullPayment = $userRequest?->latestSuccessfulFullPayment();
        $packageValue = $fullPayment?->amount_minor ?? ($userRequest?->plan?->price_min ?? 0);
        $totalPaid = $userRequest?->totalSuccessfulPaymentsMinor() ?? 0;

        return [
            $request->id,
            $request->user?->name ?? $request->user_id,
            $userRequest?->plan?->title ?? ($userRequest?->plan_id ?? '-'),
            $this->reportCurrencyConverter->formatConvertedMinor((int) $packageValue, $userRequest?->currency ?? ReportCurrencyConverter::REPORT_CURRENCY),
            $this->reportCurrencyConverter->formatConvertedMinor((int) $totalPaid, $userRequest?->currency ?? ReportCurrencyConverter::REPORT_CURRENCY),
            $request->reason ?? '-',
            $statuses[$request->status] ?? $request->status,
            $request->admin_notes ?? '-',
            $request->processedBy?->name ?? ($request->processed_by ?? '-'),
            $request->processed_at?->format('Y-m-d H:i:s') ?? '-',
            $request->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}



