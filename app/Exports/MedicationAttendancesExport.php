<?php

namespace App\Exports;

use App\Nurse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class MedicationAttendancesExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $status;
    protected $period;

    public function __construct($status = 'todas', $period = null)
    {
        $this->status = $status;
        $this->period = $period;
    }

    public function query()
    {
        $query = Nurse::query()
            ->join('orders as report_orders', 'report_orders.id', '=', 'nurses.order_id')
            ->leftJoin('patients as report_patients', 'report_patients.id', '=', 'report_orders.patient_id')
            ->leftJoin('rooms as report_rooms', 'report_rooms.id', '=', 'report_orders.room_id')
            ->leftJoin('shifts as report_shifts', 'report_shifts.id', '=', 'report_orders.shift_id')
            ->leftJoin('medicals as report_medicals', 'report_medicals.order_id', '=', 'report_orders.id')
            ->leftJoin('users as report_start_user', 'report_start_user.id', '=', 'nurses.user_id')
            ->leftJoin('users as report_end_user', 'report_end_user.id', '=', 'nurses.user_id2')
            ->select(
                'nurses.*',
                'report_orders.date_order as report_date',
                'report_orders.n_fua as report_fua',
                'report_patients.name as report_patient_name',
                'report_patients.dni as report_patient_dni',
                'report_rooms.name as report_room',
                'report_shifts.name as report_shift',
                'report_start_user.name as report_start_user',
                'report_end_user.name as report_end_user',
                DB::raw("COALESCE(NULLIF(nurses.epo2000, ''), report_medicals.epo) as report_epo2000"),
                DB::raw("COALESCE(NULLIF(nurses.epo4000, ''), report_medicals.epo4000) as report_epo4000"),
                DB::raw("COALESCE(NULLIF(nurses.iron, ''), report_medicals.iron) as report_iron"),
                DB::raw("COALESCE(NULLIF(nurses.hidroxi, ''), report_medicals.vitb12) as report_vitb12"),
                DB::raw("COALESCE(NULLIF(nurses.calcitriol, ''), report_medicals.calci) as report_calcitriol")
            )
            ->orderBy('report_orders.date_order')
            ->orderBy('nurses.patient');

        if ($this->status === 'finalizada') {
            $query->complete();
        } elseif ($this->status === 'incompleta') {
            $query->incomplete();
        }

        if ($this->period) {
            list($year, $month) = explode('-', $this->period);
            $query->whereYear('report_orders.date_order', $year)
                ->whereMonth('report_orders.date_order', $month);
        }

        return $query;
    }

    public function map($nurse): array
    {
        return [
            $nurse->report_date,
            $nurse->report_fua,
            $nurse->report_patient_name ?: $nurse->patient,
            $nurse->report_patient_dni,
            $nurse->report_room,
            $nurse->report_shift,
            $nurse->isTreatmentComplete() ? 'FINALIZADA' : 'INCOMPLETA',
            $nurse->report_start_user,
            $nurse->report_end_user,
            $nurse->report_epo2000,
            $nurse->report_epo4000,
            $nurse->report_iron,
            $nurse->report_vitb12,
            $nurse->report_calcitriol,
        ];
    }

    public function headings(): array
    {
        return [
            'Fecha de atención',
            'N° FUA',
            'Paciente',
            'DNI',
            'Módulo',
            'Turno',
            'Estado',
            'Lic. inicia',
            'Lic. finaliza',
            'EPO 2000',
            'EPO 4000',
            'Hierro',
            'Vitamina B12',
            'Calcitriol',
        ];
    }

    public function title(): string
    {
        return 'Detalle de atenciones';
    }
}
