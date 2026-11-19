<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NurseAttendanceWorkbookExport implements WithMultipleSheets
{
    protected $patients;
    protected $partialStories;
    protected $month;
    protected $daysInMonth;
    protected $today;

    public function __construct(array $patients, array $partialStories, $month, $daysInMonth, $today)
    {
        $this->patients = $patients;
        $this->partialStories = $partialStories;
        $this->month = $month;
        $this->daysInMonth = $daysInMonth;
        $this->today = $today;
    }

    public function sheets(): array
    {
        return [
            new NurseAttendanceExport($this->patients, $this->month, $this->daysInMonth, $this->today),
            new NursePartialAttendanceExport($this->partialStories),
        ];
    }
}
