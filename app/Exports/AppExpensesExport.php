<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\AppExpense;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AppExpensesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Collection $expenses
    ) {}

    public function collection()
    {
        return $this->expenses;
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'النوع',
            'المبلغ',
            'الملاحظات',
            'أضيف بواسطة',
            'آخر تحديث بواسطة',
            'تاريخ الإضافة',
            'تاريخ آخر تحديث',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->id,
            AppExpense::typeLabelFor($expense->type),
            number_format($expense->amount_minor / 100, 2),
            $expense->notes ?? '',
            $expense->creator?->name ?? '',
            $expense->updater?->name ?? '',
            $expense->created_at?->format('Y-m-d H:i:s'),
            $expense->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
