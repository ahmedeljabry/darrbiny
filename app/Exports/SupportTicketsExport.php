<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupportTicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly \Illuminate\Support\Collection $tickets
    ) {}

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'المستخدم',
            'الهاتف',
            'البريد الإلكتروني',
            'الموضوع',
            'الحالة',
            'عدد الرسائل',
            'تاريخ الإنشاء',
            'آخر تحديث',
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->id,
            $ticket->user?->name ?? $ticket->name ?? 'غير معروف',
            $ticket->user?->phone_with_cc ?? $ticket->phone_with_cc ?? '-',
            $ticket->user?->email ?? $ticket->email ?? '-',
            $ticket->subject,
            $ticket->status === 'open' ? 'مفتوحة' : ($ticket->status === 'pending' ? 'قيد المعالجة' : 'مغلقة'),
            $ticket->messages_count ?? $ticket->messages->count() ?? 0,
            $ticket->created_at?->format('Y-m-d H:i:s'),
            $ticket->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

