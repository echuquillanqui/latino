<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class NursePartialAttendanceExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $partialStories;

    public function __construct(array $partialStories)
    {
        $this->partialStories = $partialStories;
    }

    public function title(): string
    {
        return 'Historias parciales';
    }

    public function headings(): array
    {
        return [
            'Paciente',
            'DNI',
            'Fecha',
            'Turno',
            'N° HD registrado',
            'Correlativo esperado',
            '¿N° HD consecutivo?',
        ];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->partialStories as $story) {
            $rows[] = [
                $story['patient'],
                $story['dni'],
                Carbon::parse($story['date'])->format('d/m/Y'),
                $story['shift'],
                $story['nhd'] !== null ? $story['nhd'] : 'Vacío',
                $story['expected_nhd'],
                $story['nhd_consecutive'] ? 'SÍ, CONSECUTIVO' : 'NO, REVISAR',
            ];
        }

        return $rows;
    }
}
