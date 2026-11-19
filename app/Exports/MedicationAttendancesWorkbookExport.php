<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MedicationAttendancesWorkbookExport implements WithMultipleSheets
{
    protected $status;
    protected $period;

    public function __construct($status, $period)
    {
        $this->status = $status;
        $this->period = $period;
    }

    public function sheets(): array
    {
        return [
            new MedicationAttendancesExport($this->status, $this->period),
            new PatientDateMedicationSummaryExport($this->status, $this->period),
            new MonthlyMedicationTotalsExport($this->status, $this->period),
        ];
    }
}
