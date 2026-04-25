<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArrayRowsExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(
        private readonly array $headers,
        private readonly array $rows
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(function (array $row): array {
            return array_map(
                static fn (mixed $cell): mixed => $cell instanceof \DateTimeInterface
                    ? $cell->format('Y-m-d H:i:s')
                    : $cell,
                $row
            );
        });
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
