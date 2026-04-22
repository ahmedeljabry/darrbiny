<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\ReportCurrencyConverter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AppWalletAccountExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Collection $entries
    ) {}

    public function collection()
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return [
            'المرجع',
            'الحركة',
            'المصدر',
            'الوصف',
            'رقم الطلب',
            'الطرف',
            'التفاصيل',
            'المبلغ (' . ReportCurrencyConverter::REPORT_CURRENCY . ')',
            'العملة',
            'الملاحظات',
            'التاريخ',
        ];
    }

    public function map($entry): array
    {
        return [
            data_get($entry, 'reference_label'),
            data_get($entry, 'direction_label'),
            data_get($entry, 'source_label'),
            data_get($entry, 'description'),
            data_get($entry, 'order_reference'),
            data_get($entry, 'counterparty'),
            data_get($entry, 'details'),
            number_format(((int) data_get($entry, 'report_amount_minor', 0)) / 100, 2),
            data_get($entry, 'report_currency', ReportCurrencyConverter::REPORT_CURRENCY),
            data_get($entry, 'notes'),
            data_get($entry, 'occurred_at')?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
