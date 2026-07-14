<?php

namespace App\Exports;

use App\Exports\Sheets\CollectionSheetExport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SitePlanningWorkbookExport implements WithMultipleSheets
{
    public function __construct(private array $sections)
    {
    }

    public function sheets(): array
    {
        return array_map(
            fn (array $section) => new CollectionSheetExport($section['name'], $section['headings'], collect($section['rows'])),
            $this->sections
        );
    }
}
