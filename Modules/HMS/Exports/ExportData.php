<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExportData implements FromCollection, WithHeadings, WithStyles,ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function collection()
    {
        if ($this->data)
            return new Collection($this->data);
    }
    public function headings(): array
    {
        if ($this->data) {
            if(empty($this->data->toArray())){
                return ["Error: No record found"];
            }
            $keys = array_keys(collect($this->data)->first()->toArray());
            $headinds = array_map(function($value) {
                $parts = explode('_', $value);
                return strtoupper(implode(' ', $parts));
            }, $keys);
            // dd($headinds);
            return $headinds;
        }
    }

    public function styles(Worksheet $sheet){
        return [
            // Style the first row as bold text.
            1    =>[
                    'font' => [
                        'name' => 'Arial',
                        'bold' => true,
                        'color' => [
                            'rgb' => 'FFFFFF'
                        ]
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '000000',
                        ],
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_DASHDOT,
                            'color' => [
                                'rgb' => 'FFFFFF'
                            ]
                        ],
                        'top' => [
                            'borderStyle' => Border::BORDER_DASHDOT,
                            'color' => [
                                'rgb' => 'FFFFFF'
                            ]
                        ]
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'quotePrefix'    => true
                ]
            
            ];
      
    }
}
