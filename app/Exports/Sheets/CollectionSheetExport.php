<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CollectionSheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(
        private string $title,
        private array $headings,
        private Collection $rows,
    ) {
    }

    public function array(): array
    {
        return $this->rows->map(function ($row) {
            if (is_array($row)) {
                return array_values($row);
            }

            if (is_object($row) && method_exists($row, 'toArray')) {
                return array_values($row->toArray());
            }

            return (array) $row;
        })->all();
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
