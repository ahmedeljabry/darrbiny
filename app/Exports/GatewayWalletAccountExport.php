<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\ReportCurrencyConverter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GatewayWalletAccountExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $gatewayConfig,
        private readonly array $summary,
        private readonly Collection $entries
    ) {}

    public function sheets(): array
    {
        return [
            new GatewayWalletDashboardSheet($this->gatewayConfig, $this->summary, $this->entries),
            new GatewayWalletOperationsSheet($this->entries),
        ];
    }
}

class GatewayWalletDashboardSheet implements FromArray, ShouldAutoSize, WithEvents, WithStyles, WithTitle
{
    public function __construct(
        private readonly array $gatewayConfig,
        private readonly array $summary,
        private readonly Collection $entries
    ) {}

    public function array(): array
    {
        $manualIncoming = $this->entries
            ->where('entry_type', 'manual')
            ->where('direction', 'in')
            ->values();
        $manualOutgoing = $this->entries
            ->where('entry_type', 'manual')
            ->where('direction', 'out')
            ->values();

        $rows = [
            [$this->gatewayConfig['title'] ?? 'محفظة بوابة الدفع'],
            [],
            [
                'اجمالى المبيعات',
                'اجمالى الوارد',
                'اجمالى المصروفات',
                'الضريبة',
                'المتبقي لدى بوابة الدفع',
                'اجمالى التحويلات',
                'رصيد المحفظة',
                'عدد العمليات',
            ],
            [
                $this->money($this->summary['sales_minor'] ?? 0),
                $this->money($this->summary['incoming_minor'] ?? 0),
                $this->money($this->summary['gateway_fee_minor'] ?? 0),
                $this->money($this->summary['vat_minor'] ?? 0),
                $this->money($this->summary['remaining_gateway_minor'] ?? 0),
                $this->money($this->summary['transfers_minor'] ?? 0),
                $this->money($this->summary['wallet_balance_minor'] ?? 0),
                (int) ($this->summary['operations_count'] ?? 0),
            ],
            [],
            ['الوارد'],
            ['رقم المرجع', 'نوع الوارد', 'ملاحظات', 'المبلغ', 'التاريخ'],
        ];

        foreach ($manualIncoming as $entry) {
            $rows[] = [
                $entry->reference_label,
                $entry->source_label,
                $entry->notes,
                $this->money($entry->amount_minor),
                $entry->occurred_at?->format('Y-m-d H:i:s'),
            ];
        }

        $rows[] = [];
        $rows[] = ['التحويلات والمصروفات'];
        $rows[] = ['رقم المرجع', 'نوع الحركة', 'ملاحظات', 'المبلغ', 'التاريخ'];

        foreach ($manualOutgoing as $entry) {
            $rows[] = [
                $entry->reference_label,
                $entry->source_label,
                $entry->notes,
                $this->money($entry->amount_minor),
                $entry->occurred_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'الداشبورد';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
            6 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => static function (AfterSheet $event): void {
                $event->sheet->getDelegate()->setRightToLeft(true);
            },
        ];
    }

    private function money(int $amountMinor): string
    {
        return number_format($amountMinor / 100, 2).' '.ReportCurrencyConverter::REPORT_CURRENCY;
    }
}

class GatewayWalletOperationsSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
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
            'رقم المرجع',
            'الحركة',
            'المصدر',
            'الوصف / الطرف',
            'الدولة',
            'الطلب / الملاحظات',
            'المبلغ',
            'الرسوم',
            'الضريبة',
            'المبلغ المستحق',
            'التاريخ',
        ];
    }

    public function map($entry): array
    {
        return [
            $entry->reference_label,
            $entry->direction_label,
            $entry->source_label,
            $entry->description,
            $entry->country,
            $entry->order_notes,
            $this->money((int) $entry->amount_minor),
            $this->money((int) $entry->fee_minor),
            $this->money((int) $entry->vat_minor),
            $this->money((int) $entry->net_minor),
            $entry->occurred_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function title(): string
    {
        return 'العمليات';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => static function (AfterSheet $event): void {
                $event->sheet->getDelegate()->setRightToLeft(true);
            },
        ];
    }

    private function money(int $amountMinor): string
    {
        return number_format($amountMinor / 100, 2).' '.ReportCurrencyConverter::REPORT_CURRENCY;
    }
}
