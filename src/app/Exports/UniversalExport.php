<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\BeforeWriting;

class UniversalExport implements FromCollection,
    WithHeadings,
    WithColumnFormatting,
    WithEvents,
    WithStrictNullComparison
{
    private $headings;
    private $data;
    private $columnFormats;
    private $closures;
    public function __construct(array $headings, Collection $data, array $columnFormats=[])
    {
        $this->headings = $headings;
        $this->data = $data;
        $this->columnFormats = $columnFormats;
        $this->closures = [];
    }

    public function registerEvents(): array
    {
        return $this->closures;
    }

    public function setClosure($name, \Closure $closure)
    {
        $this->closures[$name] = $closure;
    }

    /**
    * @return Collection
    */
    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function columnFormats(): array
    {
        return $this->columnFormats;
    }
}
