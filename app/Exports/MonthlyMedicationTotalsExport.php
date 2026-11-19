<?php

namespace App\Exports;

use App\Nurse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonthlyMedicationTotalsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $status;
    protected $period;

    public function __construct($status, $period)
    {
        $this->status = $status;
        $this->period = $period;
    }

    public function collection()
    {
        list($year, $month) = explode('-', $this->period);

        $query = Nurse::query()
            ->join('orders as report_orders', 'report_orders.id', '=', 'nurses.order_id')
            ->leftJoin('medicals as report_medicals', 'report_medicals.order_id', '=', 'report_orders.id')
            ->whereYear('report_orders.date_order', $year)
            ->whereMonth('report_orders.date_order', $month);

        if ($this->status === 'finalizada') {
            $query->complete();
        } elseif ($this->status === 'incompleta') {
            $query->incomplete();
        }

        $totals = $query->select([
            DB::raw("SUM(COALESCE(NULLIF(nurses.epo2000, ''), report_medicals.epo, 0) + 0) as total_epo2000"),
            DB::raw("SUM(COALESCE(NULLIF(nurses.epo4000, ''), report_medicals.epo4000, 0) + 0) as total_epo4000"),
            DB::raw("SUM(COALESCE(NULLIF(nurses.iron, ''), report_medicals.iron, 0) + 0) as total_iron"),
            DB::raw("SUM(COALESCE(NULLIF(nurses.hidroxi, ''), report_medicals.vitb12, 0) + 0) as total_vitb12"),
            DB::raw("SUM(COALESCE(NULLIF(nurses.calcitriol, ''), report_medicals.calci, 0) + 0) as total_calcitriol"),
        ])->first();

        return new Collection([
            ['EPO 2000', $totals->total_epo2000 ?: 0],
            ['EPO 4000', $totals->total_epo4000 ?: 0],
            ['Hierro', $totals->total_iron ?: 0],
            ['Vitamina B12', $totals->total_vitb12 ?: 0],
            ['Calcitriol', $totals->total_calcitriol ?: 0],
        ]);
    }

    public function headings(): array
    {
        return ['Medicamento', 'Total utilizado en el mes'];
    }

    public function title(): string
    {
        return 'Totales mensuales';
    }
}
