<?php

namespace App\Exports;

use App\Nurse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PatientDateMedicationSummaryExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $status;
    protected $period;

    public function __construct($status, $period)
    {
        $this->status = $status;
        $this->period = $period;
    }

    public function query()
    {
        list($year, $month) = explode('-', $this->period);

        $query = Nurse::query()
            ->join('orders as report_orders', 'report_orders.id', '=', 'nurses.order_id')
            ->leftJoin('patients as report_patients', 'report_patients.id', '=', 'report_orders.patient_id')
            ->leftJoin('medicals as report_medicals', 'report_medicals.order_id', '=', 'report_orders.id')
            ->select(
                'report_orders.date_order as report_date',
                'report_patients.name as report_patient_name',
                'nurses.patient as nurse_patient_name',
                DB::raw("SUM(COALESCE(NULLIF(nurses.epo2000, ''), report_medicals.epo, 0) + 0) as total_epo2000"),
                DB::raw("SUM(COALESCE(NULLIF(nurses.epo4000, ''), report_medicals.epo4000, 0) + 0) as total_epo4000"),
                DB::raw("SUM(COALESCE(NULLIF(nurses.iron, ''), report_medicals.iron, 0) + 0) as total_iron"),
                DB::raw("SUM(COALESCE(NULLIF(nurses.hidroxi, ''), report_medicals.vitb12, 0) + 0) as total_vitb12"),
                DB::raw("SUM(COALESCE(NULLIF(nurses.calcitriol, ''), report_medicals.calci, 0) + 0) as total_calcitriol")
            )
            ->whereYear('report_orders.date_order', $year)
            ->whereMonth('report_orders.date_order', $month)
            ->groupBy('report_orders.date_order', 'report_patients.name', 'nurses.patient')
            ->orderBy('report_orders.date_order')
            ->orderBy('report_patients.name');

        if ($this->status === 'finalizada') {
            $query->complete();
        } elseif ($this->status === 'incompleta') {
            $query->incomplete();
        }

        return $query;
    }

    public function map($row): array
    {
        return [
            $row->report_patient_name ?: $row->nurse_patient_name,
            $row->report_date,
            $row->total_epo2000,
            $row->total_epo4000,
            $row->total_iron,
            $row->total_vitb12,
            $row->total_calcitriol,
        ];
    }

    public function headings(): array
    {
        return ['Paciente', 'Fecha de atención', 'EPO 2000', 'EPO 4000', 'Hierro', 'Vitamina B12', 'Calcitriol'];
    }

    public function title(): string
    {
        return 'Totales por paciente y fecha';
    }
}
