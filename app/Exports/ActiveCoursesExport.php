<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActiveCoursesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $courses
    ) {}

    public function collection()
    {
        return $this->courses;
    }

    public function headings(): array
    {
        return [
            'الطلب',
            'المدرب',
            'المستخدم',
            'قيمة الكورس',
            'تاريخ البدء',
        ];
    }

    public function map($course): array
    {
        $payment = $course->payments->first();
        return [
            $course->id,
            $course->trainer?->name ?? '-',
            $course->user?->name ?? '-',
            $payment ? number_format($payment->amount_minor / 100, 2) : '-',
            $course->start_date?->toDateString(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}


