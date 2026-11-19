<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class NurseAttendanceExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $patients;
    protected $month;
    protected $daysInMonth;
    protected $today;

    public function __construct(array $patients, $month, $daysInMonth, $today)
    {
        $this->patients = $patients;
        $this->month = $month;
        $this->daysInMonth = $daysInMonth;
        $this->today = $today;
    }

    public function headings(): array
    {
        $headings = ['Paciente', 'DNI', 'Secuencia'];
        $weekDays = [1 => 'Lu', 2 => 'Ma', 3 => 'Mi', 4 => 'Ju', 5 => 'Vi', 6 => 'Sá', 7 => 'Do'];

        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            $date = Carbon::createFromFormat('Y-m-d', $this->month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT));
            $headings[] = $day . ' ' . $weekDays[$date->dayOfWeekIso];
        }

        return $headings;
    }

    public function title(): string
    {
        return 'Asistencia mensual';
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->patients as $patient) {
            $row = [
                $patient['name'],
                $patient['dni'],
                $patient['sequence'] ? str_replace('-', ' - ', $patient['sequence']) : '',
            ];

            for ($day = 1; $day <= $this->daysInMonth; $day++) {
                $entries = $patient['days'][$day] ?? [];
                $date = Carbon::createFromFormat('Y-m-d', $this->month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT));
                $scheduled = in_array($date->dayOfWeekIso, $patient['expected_days'], true);

                if ($entries) {
                    $labels = [];
                    foreach ($entries as $entry) {
                        $status = $entry['status'] === 'finalized'
                            ? 'FINALIZADA'
                            : ($entry['status'] === 'partial' ? 'PARCIAL' : 'SIN DATOS');
                        $labels[] = $entry['nhd'] ? $status . ' - N° HD ' . $entry['nhd'] : $status;
                    }
                    $row[] = implode(' / ', $labels);
                } elseif (!$scheduled) {
                    $row[] = '—';
                } elseif ($date->toDateString() >= $this->today) {
                    $row[] = 'PROGRAMADO';
                } else {
                    $row[] = 'NO ASISTIÓ';
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
