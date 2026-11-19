<?php

namespace App\Http\Controllers\Administrador;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Nurse;
use App\Shift;
use Carbon\Carbon;
use DB;

class ChartController extends Controller
{
    public function orders(Request $request)
    {
	    $today = Carbon::today();
	    $mode = $request->input('mode', 'year');
	    if (!in_array($mode, ['day', 'month', 'year'], true)) {
	        $mode = 'year';
	    }

	    $year = filter_var($request->input('year'), FILTER_VALIDATE_INT);
	    $year = $year && $year >= 2000 && $year <= 2100 ? $year : (int) $today->year;
	    $month = filter_var($request->input('month'), FILTER_VALIDATE_INT);
	    $month = $month && $month >= 1 && $month <= 12 ? $month : (int) $today->month;
	    $selectedDate = $request->input('date');
	    try {
	        $selectedDate = $selectedDate ? Carbon::parse($selectedDate)->toDateString() : $today->toDateString();
	    } catch (\Exception $exception) {
	        $selectedDate = $today->toDateString();
	    }

	    $labels = [];
	    $counts = [];
	    $periodLabel = '';
	    $query = Nurse::complete();

	    if ($mode === 'year') {
	        $endDate = $year === (int) $today->year ? $today->toDateString() : Carbon::create($year, 12, 31)->toDateString();
	        $numberOfMonths = $year === (int) $today->year ? (int) $today->month : 12;
	        $monthlyCounts = $query->whereBetween('nurses.date_order', [Carbon::create($year, 1, 1)->toDateString(), $endDate])
	            ->select(DB::raw('MONTH(nurses.date_order) as period'), DB::raw('COUNT(nurses.id) as count'))
	            ->groupBy(DB::raw('MONTH(nurses.date_order)'))
	            ->pluck('count', 'period');
	        $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
	        for ($index = 1; $index <= $numberOfMonths; $index++) {
	            $labels[] = $monthNames[$index - 1];
	            $counts[] = (int) $monthlyCounts->get($index, 0);
	        }
	        $periodLabel = 'Año ' . $year;
	    } elseif ($mode === 'month') {
	        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
	        $endDate = $startDate->copy()->endOfMonth();
	        if ($year === (int) $today->year && $month === (int) $today->month) {
	            $endDate = $today->copy();
	        }
	        $daysInPeriod = $startDate->daysInMonth;
	        $dailyCounts = collect();
	        if ($startDate->lte($today)) {
	            $dailyCounts = (clone $query)->whereBetween('nurses.date_order', [$startDate->toDateString(), $endDate->toDateString()])
	                ->select(DB::raw('DAY(nurses.date_order) as period'), DB::raw('COUNT(nurses.id) as count'))
	                ->groupBy(DB::raw('DAY(nurses.date_order)'))
	                ->pluck('count', 'period');
	        }
	        $lastDayToShow = $year === (int) $today->year && $month === (int) $today->month
	            ? (int) $today->day
	            : $daysInPeriod;
	        for ($day = 1; $day <= $lastDayToShow; $day++) {
	            $labels[] = (string) $day;
	            $counts[] = (int) $dailyCounts->get($day, 0);
	        }
	        $periodLabel = $startDate->format('m/Y');
	    } else {
	        $day = Carbon::parse($selectedDate);
	        $shifts = Shift::orderBy('id')->pluck('name')->values();
	        $shiftCounts = collect();
	        if ($day->lte($today)) {
	            $shiftCounts = $query->where('nurses.date_order', $selectedDate)
	                ->select('nurses.shift', DB::raw('COUNT(nurses.id) as count'))
	                ->groupBy('nurses.shift')
	                ->pluck('count', 'shift');
	        }
	        foreach ($shifts as $shiftName) {
	            $labels[] = $shiftName;
	            $counts[] = (int) $shiftCounts->get($shiftName, 0);
	        }
	        $periodLabel = $day->format('d/m/Y');
	    }

	    return view('charts.orders', compact('counts', 'labels', 'mode', 'year', 'month', 'selectedDate', 'periodLabel'));
    }

}
