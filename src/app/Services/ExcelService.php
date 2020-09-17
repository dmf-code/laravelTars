<?php


namespace App\Services;


use App\Exports\UniversalExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Facades\Excel;

class ExcelService
{
    private function setWidth()
    {

    }

    /**
     * @param string $name
     * @param array $headings
     * @param Collection $data
     * @param array $columnFormats
     * @param array $events
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(string $name, array $headings, Collection $data, array $columnFormats=[], array $events=[])
    {
        $export = new UniversalExport($headings, $data, $columnFormats);
        /**
         * events数组配置
         *  widths => ['A' => 50, 'B' => 20]
         *  mergeCells =>'A1:D1'
         */
        $export->setClosure(BeforeSheet::class, function (BeforeSheet $event) use ($events) {

            // 设置单元格宽度
            if (isset($events['widths'])) {
                foreach ($events['widths'] as $k => $v) {
                    $event->sheet->getDelegate()->getColumnDimension($k)->setWidth($v);
                }
            }

            // 设置单元格合并
            if (isset($events['mergeCells'])) {
                $event->sheet->getDelegate()->mergeCells($events['mergeCells']);
            }

        });
        return Excel::download($export, $name);
    }
}